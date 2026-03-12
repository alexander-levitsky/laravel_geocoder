<?php

namespace Piro\Geocoder\DTO;

readonly class Location
{
    public function __construct(
        public float $lat,
        public float $lon,
        public ?string $address = null, // Опционально: нормализованный адрес от API
        public ?string $provider = null  // Опционально: кто именно ответил (Dadata/MapTiler)
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            lat: (float) $data['lat'],
            lon: (float) $data['lon'],
            address: $data['address'] ?? null,
            provider: $data['provider'] ?? null
        );
    }
}
