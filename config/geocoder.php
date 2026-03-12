<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Geocoder Driver
    |--------------------------------------------------------------------------
    | Здесь указываем, какой сервис будет основным.
    */
    'default' => env('GEOCODER_DRIVER', 'dadata'),

    'providers' => [
        'dadata' => [
            'api_key' => env('DADATA_API_KEY'),
        ],
        'maptiler' => [
            'api_key' => env('MAPTILER_API_KEY'),
        ],
    ],
];
