<?php

namespace Piro\Geocoder\DTO;

readonly class Location
{
    public function __construct(
        public ?string $address = null,
        public ?Place $region = null,
        public ?Place $city = null,
        public ?Place $district = null,
        public ?Place $area = null,
        public ?Place $settlement = null,
        public ?Place $place = null,
        public ?string $provider = null,
    ) {}
}
