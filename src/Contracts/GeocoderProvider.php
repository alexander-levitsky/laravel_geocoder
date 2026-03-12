<?php

namespace Piro\Geocoder\Contracts;

use Piro\Geocoder\DTO\Location;

interface GeocoderProvider
{

    public function reverse(float $latitude, float $longitude): ?Location;
}
