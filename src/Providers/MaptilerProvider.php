<?php

namespace Piro\Geocoder\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Piro\Geocoder\Contracts\GeocoderProvider;
use Piro\Geocoder\DTO\Location;
use RuntimeException;

class MaptilerProvider implements GeocoderProvider
{
    private const string GEOCODING_URL = 'https://api.maptiler.com/geocoding/{longitude},{latitude}.json';
    private const int CACHE_TTL_MINUTES = 60;
    private const int DEFAULT_LIMIT = 1;
    private const string LANGUAGE = 'en';

    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function reverse(float $latitude, float $longitude): ?Location
    {
        $this->validateCoordinates($latitude, $longitude);

        $params = $this->buildRequestParams();
        $cacheKey = $this->buildCacheKey($latitude, $longitude, $params);

        $response = Cache::remember(
            $cacheKey,
            self::CACHE_TTL_MINUTES * 60,
            fn() => $this->makeRequest($latitude, $longitude, $params)
        );

        if ($response->failed()) {
            $this->handleFailedResponse($response);
            return null;
        }

        $feature = $this->extractFirstFeature($response);

        if (empty($feature)) {
            return null;
        }

        return $this->buildLocation($feature);
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

    private function buildRequestParams(): array
    {
        return [
            'key' => $this->apiKey,
            'limit' => self::DEFAULT_LIMIT,
            'language' => self::LANGUAGE,
        ];
    }

    private function buildCacheKey(float $latitude, float $longitude, array $params): string
    {
        $data = [
            'lat' => $latitude,
            'lon' => $longitude,
            'params' => $params,
        ];

        return sprintf('maptiler_geo_%s', md5(json_encode($data)));
    }

    private function makeRequest(float $latitude, float $longitude, array $params): Response
    {
        $url = str_replace(
            ['{latitude}', '{longitude}'],
            [$latitude, $longitude],
            self::GEOCODING_URL
        );

        return Http::withOptions([
            'timeout' => 5,
            'retry' => [3, 100],
        ])
            ->get($url, $params);
    }

    private function extractFirstFeature(Response $response): ?array
    {
        $data = $response->json();

        if (empty($data['features']) || !is_array($data['features'])) {
            return null;
        }

        return $data['features'][0] ?? null;
    }

    private function buildLocation(array $feature): Location
    {
        $coordinates = $this->extractCoordinates($feature);

        return new Location(
            lat: $coordinates['lat'],
            lon: $coordinates['lon'],
            address: $this->buildAddressString($feature),
            provider: 'maptiler'
        );
    }

    private function extractCoordinates(array $feature): array
    {
        $center = $feature['center'] ?? $feature['geometry']['coordinates'] ?? [0, 0];

        // MapTiler возвращает координаты в формате [longitude, latitude]
        return [
            'lon' => (float) ($center[0] ?? 0),
            'lat' => (float) ($center[1] ?? 0),
        ];
    }

    private function buildAddressString(array $feature): string
    {
        $addressComponents = array_filter([
            $this->buildStreetPart($feature),
            $this->regionChanger($feature['context'][0] ?? null),
            $this->regionChanger($feature['context'][1] ?? null),
            $this->regionChanger($feature['context'][2] ?? null),
            $this->regionChanger($feature['context'][3] ?? null),
        ]);

        return implode(', ', $addressComponents);
    }

    private function handleFailedResponse(Response $response): void
    {
        $context = [
            'status' => $response->status(),
            'body' => $response->body(),
        ];

        if (app()->environment('local', 'testing')) {
            throw new RuntimeException(
                "MapTiler API request failed: {$response->status()} - {$response->body()}"
            );
        }

        logger()->error('MapTiler API request failed', $context);
    }

    private function buildStreetPart(array $feature): ?string {
        $street = $feature['text'] ?? null;
        $house = $feature['address'] ?? null;

        if (!$street && !$house) {
            return null;
        }

        if ($street && $house) {
            return $house . ', ' . $street;
        }

        return $street ?? $house;
    }


    /** Замена региона при необходимости
     * @param array|null $feature
     * @return string|null
     */
    private function regionChanger(?array $feature): ?string
    {
        if (!array_key_exists('ref', $feature) || !$feature['text']) {
            return null;
        }

        if(str_starts_with($feature['id'] , 'country')){
            return null;
        };

        if(str_starts_with($feature['id'] , 'postal_code')){
            return null;
        };


        return match ($feature['ref']) {
            'osm:r72639' => 'Респ Крым',
            default => $feature['text'],
        };
    }
}
