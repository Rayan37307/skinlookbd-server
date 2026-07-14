# Introduction

REST API for the SkinLookBD skincare storefront, admin dashboard, and mobile clients.

<aside>
    <strong>Base URL</strong>: <code>http://skinlookserver.test</code>
</aside>

    This is the API reference for SkinLookBD, a single-vendor skincare e-commerce backend built with Laravel.
    All endpoints are versioned under `/api/v1` and return JSON.

    <aside>Public endpoints (catalog browsing, cart) need no authentication. Customer endpoints (checkout, orders, wishlist)
    require a Sanctum bearer token obtained via <code>POST /api/v1/auth/login</code> or <code>/register</code>. Admin endpoints
    additionally require the authenticated user to hold one of the <code>super-admin</code>, <code>order-manager</code>, or
    <code>catalog-manager</code> roles.</aside>

