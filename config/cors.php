<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://skinlookbd.com',
        'https://www.skinlookbd.com',
        'https://ssr.skinlookbd.com',
        'http://localhost:4321',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    // The guest cart flow (CartController) returns the cart token in this custom
    // response header so the storefront can persist it and keep reusing the same
    // guest cart on subsequent requests. Without it listed here, browsers silently
    // withhold it from JS (only a small built-in safelist of headers is exposed by
    // default per the CORS spec), so `X-Cart-Token` was never actually readable
    // cross-origin — the item still got added server-side, but the token was lost,
    // so every next request started a brand-new empty guest cart.
    'exposed_headers' => ['X-Cart-Token'],

    'max_age' => 0,

    'supports_credentials' => false,

];
