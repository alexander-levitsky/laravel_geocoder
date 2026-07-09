<?php

namespace Piro\Geocoder\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Piro\Geocoder\Contracts\GeocoderProvider;
use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\DTO\Location;
use Piro\Geocoder\DTO\Place;
use RuntimeException;

class GisProvider implements GeocoderProvider
{
    private const string SUGGESTION_URL = 'https://catalog.api.2gis.com/3.0/items/geocode';
    private const string REGIONS_URL = 'catalog.api.2gis.com/2.0/region/search';

    public function __construct(
        private readonly string $token,
        private readonly string $locale = 'ru_RU',
    ) {}

    private function isRussia(float $latitude, float $longitude): bool
    {
        $response = $this->makeRequest([
            'key' => $this->token,
            'q' => "$longitude,$latitude",
            'country_code_filter'=>'ru',
            'fields' => "items.bounds,items.country_code",
            'type' => "region",
        ], self::REGIONS_URL);

        $result = $response->json()['result']['items'][0] ?? [];

        return strtolower($result['country_code'] ?? '') === 'ru';
    }

    public function reverse(float $latitude, float $longitude): ?Location
    {
        if($this->isRussia($latitude, $longitude) === false) {
            return null;
        }

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
            'key' => $this->token,
            'locale' => $this->locale,
            'type' => 'adm_div,building',
            'fields' => 'items.adm_div,items.country,items.sources,items.filters',
            'search_input_method' => 'software_generated',
            'radius' => 2000,
            'sort' => 'distance',
            'lat' => $latitude,
            'lon' => $longitude,
            'page_size'=>5,
            'page'=>1,
        ];
    }

    private function makeRequest(array $params, string $endpoint = self::SUGGESTION_URL): Response
    {
        return Http::withHeaders($this->buildHeaders())
            ->timeout(5)
            ->retry(3, 100)
            ->get($endpoint, $params);
    }

    private function buildHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    private function extractResponseData(Response $response): array
    {
        $data = $response->json();

        return $data['result']['items'] ?? [];
    }

    private function buildLocation(array $data): ?Location
    {
        $country = $this->extractAdministrativePart($data, 'country');
        $region =  $this->extractAdministrativePart($data, 'region');

        if ($this->skipResult($country)) return null;

        $city = $this->extractAdministrativePart($data, 'city');
        $district = $this->extractAdministrativePart($data, 'district');
        $area = $this->extractAdministrativePart($data, 'living_area');
        $settlement = $this->extractAdministrativePart($data, 'settlement');

        $place = $this->extractAdministrativePart($data, 'place');

        return new Location(
            address: $this->extractAddressString($data, [$settlement,$area,$district,$city,$region]),
            region: $region,
            city: $city,
            district: $district,
            area: $area,
            settlement: $settlement,
            place: $place,
            provider: GeoProviders::GIS
        );
    }

    private function skipResult(?Place $country = null): bool
    {
        // Если нет страны или страна Россия, не пропускаем (ID=1 Россия)
        $isRussia = !$country || $country->id === 1;
        return !$isRussia;
    }

    private function extractAddressString(array $data, array $parts): string
    {
        $building = array_find($data, fn($item) => $item['type'] === 'building');
        return $building['full_name'] ?? $this->fallbackAddressString($parts);
    }

    private function fallbackAddressString(array $parts) : string {
        $names = array_map(fn($item)=>$item?->name, $parts);
        return implode(', ', array_filter($names));
    }

    private function extractAdministrativePart(array $data, string $level): ?Place
    {
        $itemData = null;

        foreach ($data as $item) {

            if (($item['subtype'] ?? null) === $level) {
                $itemData = $item;
                break;
            }

            if (empty($item['adm_div'])) continue;

            foreach ($item['adm_div'] as $part) {
                if ($part['type'] === $level) {
                    $itemData = $part;
                    break 2;
                }
            }
        }

        if (!$itemData) {
            return null;
        }

        return new Place(id: (int) $itemData['id'], name: $itemData['name']);
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
