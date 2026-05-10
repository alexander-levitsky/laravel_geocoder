<?php

namespace Piro\Geocoder\DTO;

readonly class City
{
    public function __construct(
        public string $type,
        public string $shortType,
        public string $name,
        public string $text,
    ) {}
}
