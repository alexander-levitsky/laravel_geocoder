<?php

namespace Piro\Geocoder\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Piro\Geocoder\Contracts\GeocoderProvider;
use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\DTO\Location;
use Piro\Geocoder\DTO\Place;
use RuntimeException;

class MaptilerProvider implements GeocoderProvider
{
    private const string GEOCODING_URL = 'https://api.maptiler.com/geocoding/{longitude},{latitude}.json';
    private const int DEFAULT_LIMIT = 1;
    private const string LANGUAGE = 'en';

    public function __construct(
        private readonly string $apiKey,
    ) {}

    public function reverse(float $latitude, float $longitude): ?Location
    {
        $this->validateCoordinates($latitude, $longitude);

        $params = $this->buildRequestParams();

        $response = $this->makeRequest($latitude, $longitude, $params);

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
            'language' => 'en,ru',
        ];
    }

    private function makeRequest(float $latitude, float $longitude, array $params): Response
    {
        $url = str_replace(
            ['{latitude}', '{longitude}'],
            [$latitude, $longitude],
            self::GEOCODING_URL
        );

        return Http::withHeaders([])
            ->timeout(5)
            ->retry(3, 100)
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
            address: $this->buildAddressString($feature),
            region: $this->buildRegion($feature),
            city: $this->buildCity($feature),
            district: $this->buildSubregion($feature),
            settlement: $this->buildSettlement($feature),
            provider: GeoProviders::MAPTILER
        );
    }

    private function buildRegion(array $feature): ?Place
    {
        $regionData = $this->getContextItem($feature['context'], 'state');

        if (empty($regionData)) return null;

        return new Place(
            id: 0,
            name: $regionData['text'],
        );
    }

    private function buildCity(array $feature): ?Place
    {
        $cityData = $this->getContextItem($feature['context'], 'city');

        if (empty($cityData)) return null;

        return new Place(
            id: 0,
            name: $cityData['text'],
        );
    }

    private function buildSubregion(array $feature): ?Place
    {
        $subregionData = $this->getContextItem($feature['context'], 'municipal_district');

        if (!$subregionData) {
            $subregionData = $this->getContextItem($feature['context'], 'suburb');
        }

        if (!$subregionData) return null;

        return new Place(
            id: 0,
            name: $subregionData['text'],
        );
    }

    private function buildSettlement(array $feature): ?Place
    {
        $subregionData = $this->getContextItem($feature['context'], 'village');

        if (!$subregionData) return null;

        return new Place(
            id: 0,
            name: $subregionData['text'],
        );
    }

    private function getContextItem(array $context, string $itemType): ?array
    {
        if ($itemType === 'municipal_district') {
            $searchResults = array_filter($context, fn($item)=>str_starts_with($item['id'], $itemType));
        } else {
            $searchResults = array_filter($context, fn($item)=>($item['place_designation'] ?? null) == $itemType);
        }

        return array_first($searchResults) ?? null;
    }

    private function getAdminArea(array $context, string $itemType): array
    {
        return array_filter($context, fn($item)=>($item['place_designation'] ?? null) == $itemType);
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
        $street = $this->getText($feature);
        $house = $feature['address'] ?? null;

        if (!$street && !$house) {
            return null;
        }

        if ($street && $house) {
            return $house . ', ' . $street;
        }

        return $street ?? $house;
    }


    private function getText(array $feature) : ?string
    {
        $key = 'text';

        if (!empty($feature['language_' . self::LANGUAGE]) && $feature['language_' . self::LANGUAGE] !== 'uk') {
            $key = 'text_' . self::LANGUAGE;
        }

        return $feature[$key] ?? null;
    }


    /** Замена региона при необходимости
     * @param array|null $feature
     * @return string|null
     */
    private function regionChanger(?array $feature): ?string
    {
        if (!$feature){
            return null;
        }

        if (!array_key_exists('ref', $feature) || !$feature['text']) {
            return null;
        }

        if(str_starts_with($feature['id'] , 'country')){
            return null;
        };

        if(str_starts_with($feature['id'] , 'postal_code')){
            return null;
        };

        if(str_starts_with($feature['id'] , 'major_landform')){
            return null;
        };


        return match ($feature['ref']) {
            'osm:r72639' => 'Респ Крым',
            default => $this->getText($feature),
        };
    }
}
