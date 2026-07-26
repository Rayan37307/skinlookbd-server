# Navigation menu

A self-referencing `menus` table drives the storefront's nav — same pattern as `Category`
(`parent_id`), managed in the admin panel, exposed as a nested tree via one public API endpoint.

## Data model (`app/Models/Menu.php`)

| Column        | Notes                                                                 |
|---------------|------------------------------------------------------------------------|
| `parent_id`   | Nullable, self-referencing. **Cascades on delete** (unlike `Category`, which nulls children out) — a submenu with no container item isn't useful on its own. |
| `label`       | Display text.                                                          |
| `type`        | `category` \| `custom_url` \| `button`.                                |
| `target`      | A category **slug** (when `type = category`) or a URL/path (otherwise). |
| `style`       | Nullable free text; currently one real value, `highlight`, for things like a "Sale" badge. |
| `sort_order`  | Small int, default 0. Drives both the admin table's drag-to-reorder and the API tree's ordering. |
| `is_active`   | Only active items are returned by the public endpoint.                 |

## Admin (Filament)

`App\Filament\Resources\Menus\MenuResource` — under the "Catalog" nav group.

- The **target** field is type-aware: pick `category`, and it becomes a searchable Select of
  real category names (storing the slug); pick `custom_url`/`button`, and it becomes a plain URL
  text input. Same conditional-field pattern already used for image-vs-video in
  `ProductImageRelationManager`.
- The table is `->reorderable('sort_order')` with a **Parent item** filter, so reordering within
  one branch (e.g. all of "Skincare"'s children) is a matter of filtering to that parent first,
  then dragging. This is the "simple parent_id + sort_order editor" — there's no full nested
  drag-and-drop tree widget.
- `is_active` is a plain toggle in the form and a filterable column in the table.

## Public API

`GET /api/v1/menu` (`MenuController@index`, no auth) — returns active items only, as a tree:

```json
{
  "menu": [
    {
      "id": 1,
      "label": "Skincare",
      "type": "category",
      "target": "skincare",
      "style": null,
      "children": [
        { "id": 2, "label": "Cleansers", "type": "category", "target": "cleansers", "style": null, "children": [] }
      ]
    },
    {
      "id": 8,
      "label": "Sale",
      "type": "button",
      "target": "/products?label=sale",
      "style": "highlight",
      "children": []
    }
  ]
}
```

Same node shape as `CategoryResource` (`id`/`name-or-label`/`slug-or-target`/`children`), so it
drops into the frontend's existing `CategoryNode`-style tree rendering (`TopNav.astro`) with a
minimal type change, not a new rendering path.

Implementation note: unlike `CategoryController`, which only eager-loads one level of children
(fine for categories — 2 levels max by design), `MenuController` loads **all** active menu items
in a single query and builds the tree in PHP via `groupBy('parent_id')` + `setRelation('children',
...)`. That supports arbitrary nesting depth with no N+1, since the menu table is small.

## Seed data

`MenuSeeder` builds a real nav matching `CategorySeeder`'s actual tree (Skincare/Sun Care/Hair
Care and their real subcategories), plus a "New Arrivals" link and a highlighted "Sale" button —
so there's something real to look at without manual setup. Wired into `DatabaseSeeder` after
`ProductSeeder`.
