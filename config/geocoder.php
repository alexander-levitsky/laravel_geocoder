<?php

use Piro\Geocoder\Contracts\GeoProviders;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Geocoder Driver
    |--------------------------------------------------------------------------
    | Здесь указываем, какой сервис будет основным.
    */
    'default' => env('GEOCODER_DRIVER', GeoProviders::GIS),

    'providers' => [
        GeoProviders::MAPTILER => [
            'api_key' => env('MAPTILER_API_KEY'),
        ],
        GeoProviders::GIS => [
            'api_key' => env('GIS_API_KEY'),
        ],
    ],
];
