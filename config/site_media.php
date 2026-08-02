<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site Media Slots
    |--------------------------------------------------------------------------
    |
    | Every valid `site_media.key` value, keyed by slot id. Adding a new slot
    | (e.g. a new banner placement) only means adding an entry here — no
    | migration needed, since site_media is a key-value table.
    |
    | `toggleable` controls whether the admin UI exposes an on/off switch for
    | the slot (the logo can't be turned off, so it's excluded).
    |
    */

    'slots' => [
        'logo' => [
            'label' => 'Logo',
            'toggleable' => false,
        ],
        'footer_logo' => [
            'label' => 'Footer Logo',
            'toggleable' => false,
        ],
        'hero_banner_desktop' => [
            'label' => 'Hero Banner — Desktop',
            'toggleable' => true,
            'group' => 'hero_banner',
        ],
        'hero_banner_mobile' => [
            'label' => 'Hero Banner — Mobile',
            'toggleable' => true,
            'group' => 'hero_banner',
        ],
        'footer_ribbon' => [
            'label' => 'Footer Ribbon',
            'toggleable' => true,
        ],
    ],

];
