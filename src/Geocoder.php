<?php

namespace Piro\Geocoder;

use Piro\Geocoder\Contracts\GeocoderProvider;
use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\DTO\Location;
use Piro\Geocoder\Providers\DadataProvider;
use Piro\Geocoder\Providers\MaptilerProvider;

class Geocoder implements GeocoderProvider
{

    private GeocoderProvider $mainProvider;
    /**
     * @var GeocoderProvider[]
     */
    private array $fallbackProviders = [];

    public function __construct()
    {

        $providers = [];

        if (empty( config('geocoder.providers')[ config('geocoder.default')])) {
            throw new \Error('Unsupported provider: ' . config('geocoder.default'));
        }

        foreach (config('geocoder.providers') as $name=>$config) {
            $providers[$name] = match ($name) {
                GeoProviders::DADATA => new DadataProvider($config['api_key']),
                GeoProviders::MAPTILER => new MaptilerProvider($config['api_key']),
                default => throw new \Error('Unsupported provider: ' . $name),
            };
        }

        $this->mainProvider = $providers[config('geocoder.default')];
        unset($providers[config('geocoder.default')]);
        $this->fallbackProviders = $providers;

    }

    public function reverse(float $latitude, float $longitude): ?Location
    {
        if ($result = $this->mainProvider->reverse(latitude: $latitude, longitude: $longitude)) {
            return $result;
        }

        foreach ($this->fallbackProviders as $provider) {
            $result = $provider->reverse(latitude: $latitude, longitude: $longitude);
            if ($result) {
                return $result;
            }
        }
    }

}
