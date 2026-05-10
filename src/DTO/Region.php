<?php

namespace Piro\Geocoder\DTO;

readonly class Region
{
    public function __construct(
        public string $type,
        public string $shortType,
        public string $name,
        public string $text,
    ) {}
}
