<?php

namespace Piro\Geocoder\DTO;

readonly class Location
{
    public function __construct(
        public float $lat,
        public float $lon,
        public ?string $address = null,
        public Region $region,
        public City $city,
        public ?Subregion $subregion = null,
        public ?string $provider = null,
    ) {}
}
