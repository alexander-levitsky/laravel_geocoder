<?php

namespace Piro\Geocoder\DTO;

readonly class Place
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
