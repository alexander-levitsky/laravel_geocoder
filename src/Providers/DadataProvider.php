<?php

namespace Piro\Geocoder\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Piro\Geocoder\Contracts\GeocoderProvider;
use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\DTO\City;
use Piro\Geocoder\DTO\Location;
use Piro\Geocoder\DTO\Region;
use Piro\Geocoder\DTO\Subregion;
use RuntimeException;

class DadataProvider implements GeocoderProvider
{
    private const string SUGGESTION_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/geolocate/address';
    private const int DEFAULT_COUNT = 1;

    public function __construct(
        private readonly string $token,
    ) {}

    public function reverse(float $latitude, float $longitude): ?Location
    {
        $this->validateCoordinates($latitude, $longitude);

        $params = $this->buildRequestParams($latitude, $longitude);

        $response = $this->makeRequest($params);

        if ($response->failed()) {
            $this->handleFailedResponse($response);
            return null;
        }

        $data = $this->extractResponseData($response);

        if (empty($data)) {
            return null;
        }

        return $this->buildLocation($data);
    }

    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90 degrees.');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180 degrees.');
        }
    }

    private function buildRequestParams(float $latitude, float $longitude): array
    {
        return [
            'lat' => $latitude,
            'lon' => $longitude,
            'count' => self::DEFAULT_COUNT,
        ];
    }

    private function makeRequest(array $params): Response
    {
        return Http::withHeaders($this->buildHeaders())
            ->timeout(5)
            ->retry(3, 100)
            ->post(self::SUGGESTION_URL, $params);
    }

    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'Token ' . $this->token,
            'Accept' => 'application/json',
        ];
    }

    private function extractResponseData(Response $response): array
    {
        $data = $response->json();

        return $data['suggestions'][0]['data'] ?? [];
    }

    private function buildLocation(array $data): Location
    {
        return new Location(
            lat: $this->extractLatitude($data),
            lon: $this->extractLongitude($data),
            address: $this->buildAddressString($data),
            region: $this->buildRegion($data),
            city: $this->buildCity($data),
            subregion: $this->buildSubregion($data),
            provider: GeoProviders::DADATA
        );
    }

    private function extractLatitude(array $data): float
    {
        return (float) ($data['geo_lat'] ?? 0);
    }

    private function extractLongitude(array $data): float
    {
        return (float) ($data['geo_lon'] ?? 0);
    }

    private function buildAddressString(array $data): string
    {
        $addressParts = array_filter([
            $this->buildStreetPart($data),
            $data['settlement_with_type'] ?? null,
            $data['area_with_type'] ?? null,
            $data['city_with_type'] ?? null,
            $data['region_with_type'] ?? null,
        ]);

        return implode(', ', $addressParts);
    }

    private function buildRegion(array $data): ?Region
    {
        return new Region(
            type: $data['region_type_full'],
            shortType: $data['region_type'],
            name: $data['region'],
            text: $data['region_with_type'],
        );
    }

    private function buildCity(array $data): ?City
    {
        return new City(
            type: $data['city_type_full'],
            shortType: $data['city_type'],
            name: $data['city'],
            text: $data['city_with_type'],
        );
    }

    private function buildSubregion(array $data): ?Subregion
    {
        if ($data['settlement_with_type'] === null) {
            return null;
        }

        return new Subregion(
            type: $data['settlement_type_full'],
            shortType: $data['settlement_type'],
            name: $data['settlement'],
            text: $data['settlement_with_type'],
        );
    }

    private function buildStreetPart(array $data): ?string
    {
        $street = $data['street_with_type'] ?? null;
        $house = $data['house'] ?? null;

        if (!$street && !$house) {
            return null;
        }

        if ($street && $house) {
            return $street . ' ' . $house;
        }

        return $street ?? $house;
    }

    private function handleFailedResponse(Response $response): void
    {
        if (app()->environment('local', 'testing')) {
            throw new RuntimeException(
                "Dadata API request failed: {$response->status()} - {$response->body()}"
            );
        }

        // В production просто логируем ошибку
        logger()->error('Dadata API request failed', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);
    }
}
