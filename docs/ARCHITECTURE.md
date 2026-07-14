# Architecture

SkinLookBD is a Laravel 13 REST API for a single-vendor skincare e-commerce store targeting
Bangladesh. It is the backend only — a Next.js storefront and a React admin dashboard consume
it over HTTP. This document explains how the codebase is organized and the reasoning behind the
non-obvious decisions. For the endpoint-by-endpoint reference, see [API Documentation](#api-documentation)
below.

## Tech stack

| Concern | Choice | Why |
|---|---|---|
| Framework | Laravel 13, PHP 8.4 | Mature BD-market packages (couriers, payment gateways), Boost/Pint/Pest tooling |
| Hosting target | Namecheap shared hosting (Apache/LiteSpeed + PHP-FPM) | No persistent app server — rules out Octane and a standalone Redis service |
| Database | MySQL/MariaDB in production, SQLite in tests | Shared hosting provisions MySQL, not Postgres |
| Auth | Laravel Sanctum (bearer tokens) | Stateless, works for a decoupled SPA/mobile client |
| Authorization | spatie/laravel-permission | Role-based access for staff (`super-admin`, `order-manager`, `catalog-manager`) |
| Cache/Queue | `database` driver for both | No Redis on shared hosting; queue processed via `cron` → `schedule:run` → `queue:work --stop-when-empty` |
| File storage | Local disk (`storage/app/public`, symlinked to `public/storage`) | Upgrade path to S3/R2 once on a VPS |
| API docs | knuckleswtf/scribe | Generates OpenAPI + Postman + HTML from routes and FormRequest rules |

## Request flow and directory layout

Controllers live under `app/Http/Controllers/Api/V1/{Domain}` — one namespace per module
(`Auth`, `Me`, `Catalog`, `Cart`, `Checkout`, `Orders`, `Payments`, `Shipping`, `Wishlist`,
`Admin`). Every controller method:

1. Type-hints a `FormRequest` subclass from `app/Http/Requests/{Domain}` for validation (`authorize()` is
   `true` for all of them — authorization is enforced by route middleware/role checks, not per-field
   request authorization, except where ownership matters, e.g. `AddressPolicy`).
2. Returns an `Http\Resources` class for consistent JSON shaping.
3. Delegates any non-trivial cross-cutting logic to `app/Services`:
   - `CartService` — resolves the current cart (guest-token or user-based) and merges a guest
     cart into a user's cart on login/register.
   - `ShippingService` — matches an address to a `ShippingZone` and returns a charge/ETA, with a
     flat-rate fallback when no zone matches.
   - `OrderService` — cancel/refund/deliver transitions shared between the customer-facing
     `Orders\OrderController` and the admin `Admin\OrderController`, so restock/inventory-log logic
     isn't duplicated.

Routes are split across `routes/api.php` (public + customer-facing, under `/api/v1`) and
`routes/admin.php` (included from `api.php` under `/api/v1/admin`, `auth:sanctum` + `role:...`
middleware per sub-group). The split exists purely because `api.php` was becoming unwieldy — there's
no functional reason admin routes couldn't live in the same file.

## Auth & authorization

- **Customers** register/log in via `POST /api/v1/auth/register` or `/login` and receive a Sanctum
  token (`Authorization: Bearer {token}`). Phone verification is a separate OTP flow
  (`/auth/otp/send`, `/auth/otp/verify`) — registration does not require a verified phone.
- **Staff** are regular `User` records with one of three spatie roles: `super-admin`,
  `order-manager`, or `catalog-manager`. There is no separate "admin" model — the same login
  endpoint and token mechanism works for everyone; role gates which routes succeed.
- **Role middleware** (`role:super-admin|catalog-manager` etc.) is registered in `bootstrap/app.php`
  — this registration is *not* automatic under Laravel 11+'s new middleware configuration and had
  to be added explicitly (`Spatie\Permission\Middleware\RoleMiddleware` and friends).
- **Ownership checks** for resources scoped to a specific user (addresses, orders, cart items) are
  done either via a Policy (`AddressPolicy`) or a manual `abort_unless($resource->user_id === ...)`
  check in the controller — a Policy didn't fit cart items cleanly since a cart can belong to a
  guest token rather than a `User`.

### Roles at a glance

| Role | Can do |
|---|---|
| `customer` | Default role assigned at registration. Own cart, orders, wishlist, addresses, reviews. |
| `catalog-manager` | Categories, products, variants, images, inventory adjustments, coupons, banners, review moderation. |
| `order-manager` | Order listing/status/refunds, COD confirmation, dashboard, customers, CSV export. |
| `super-admin` | Everything above, plus staff account management. |

## Data model

Core tables and what they're for (see `database/migrations` for exact columns):

- **users / addresses / otps** — accounts (customers and staff share the `users` table),
  saved shipping/billing addresses, and phone-verification codes.
- **categories** (self-referencing `parent_id`), **skin_types**, **products**, **product_images**,
  **product_variants** (SKU/size/price-override/stock), **product_skin_type** (pivot) — the catalog.
- **carts / cart_items** — one cart per user (`user_id`) or per guest (`session_token`).
- **coupons / coupon_usages** — percent/flat discounts with usage limits, redemption tracked per
  order to enforce `max_uses` / `max_uses_per_user`.
- **orders / order_items / payments** — an order snapshots product name/SKU/price at purchase time
  (so later catalog edits never rewrite order history) and the shipping address fields are copied
  onto the order rather than referenced live.
- **shipping_zones / shipping_rates** — a zone matches one or more city/area strings; a rate is a
  flat charge + ETA. No zone match falls back to a default charge (`ShippingService`).
- **reviews** — tied to an `order_item_id` as proof of purchase; created `pending`, requires admin
  approval before appearing on the public product page.
- **wishlists** — simple user↔product pivot with a uniqueness constraint.
- **inventory_logs** — append-only ledger of every stock change (`sale`, `restock`, `adjustment`,
  `return`), polymorphically referencing what caused it (an `OrderItem` for sales/returns).
- **banners** — homepage promo content with a live/active window.
- **audit_logs** — polymorphic action log; currently written for staff CRUD and order
  status-change/refund actions (the highest-value accountability points), not every write.

### Money

All prices/totals are stored as **integers** (BDT, no paisa subdivision in practice for this
market) — never floats/decimals. This avoids floating-point rounding issues in discount/total
math entirely.

## Key flows

**Guest cart → account.** A guest cart is identified by an `X-Cart-Token` header (a UUID the
client stores and replays). `CartService::mergeGuestCartIntoUser()` is called from both
`register()` and `login()` in `AuthController` — if the client sends the guest token on the
auth request, matching cart items are merged (quantities summed) into the user's cart and the
guest cart is deleted.

**Checkout** (`CheckoutController::store`) runs entirely inside one DB transaction:
stock is (re-)checked per item → address ownership is verified → an optional coupon is validated
and its discount computed → shipping is priced via `ShippingService` → the `Order` and
`OrderItem`s are created with a snapshot of product/price data → variant stock is decremented with
a matching `InventoryLog` row → a `Payment` record is created (`pending`) → the cart is cleared.

**Order status lifecycle** is a hard-coded transition map in `Admin\OrderController`:
`pending → confirmed → processing → shipped → delivered`, with `cancelled` reachable from any
pre-shipped state and `returned` reachable *only* through the refund endpoint (not the generic
status-update endpoint) since a return implies restocking + a payment refund, which is
purpose-specific logic in `OrderService::refund()`. Marking an order `delivered` on a COD order
also marks its payment `paid`, since COD money is only collected on delivery.

**Coupon validation** (`Coupon::isRedeemableBy()`) checks active flag, date window, minimum order
value, global usage cap, and per-user usage cap — used identically by the pre-checkout
`/coupons/validate` preview endpoint and the real redemption inside checkout, so the preview can
never show a discount that checkout then rejects.

## API documentation

Generated via [Scribe](https://scribe.knuckles.wtf/laravel/) from routes, FormRequest validation
rules, and `@group`/method docblocks on controllers:

- `public/docs/index.html` — browsable HTML reference (open directly in a browser, or host it)
- `public/docs/openapi.yaml` — OpenAPI 3.0.3 spec (import into any OpenAPI-aware tool/codegen)
- `public/docs/collection.json` — Postman collection

Regenerate after changing routes, FormRequest rules, or controller docblocks:

```bash
php artisan scribe:generate
```

Response examples are only auto-generated (via live "response calls") for public, side-effect-free
`GET` endpoints (`products`, `categories`, `banners`, `shipping/estimate`) — see the `only` list in
`config/scribe.php`. Authenticated/admin endpoints are documented from their validation rules and
docblocks only; exercising those live would require seeding a real Sanctum token before every docs
build, which wasn't worth the fragility for a docs build step.

## Testing

Pest feature tests (`tests/Feature`) exercise every endpoint against an in-memory SQLite database
(`RefreshDatabase`, wired in `tests/Pest.php`, which also seeds roles before each test).
`tests/Pest.php` exposes two shared helpers: `actingAsUser()` (plain customer) and
`actingAsAdmin(string $role = 'super-admin')`. Run the suite with:

```bash
php artisan test
vendor/bin/pint --dirty   # or without --dirty outside a git repo
```

## Deliberately deferred

Per the original build plan, these are the remaining pieces, intentionally left until specific
third-party providers are chosen (see the root `README.md` for the phase list):

- Real SMS gateway for OTP delivery — currently `SendOtpSmsJob` just logs the code.
- Courier integrations (Pathao/Steadfast/RedX) for label generation and tracking webhooks.
- An online payment gateway (SSLCommerz/bKash/Nagad) — the `Payment` model's `method`/`status`
  columns and the checkout flow are already gateway-agnostic, so adding one is additive.
