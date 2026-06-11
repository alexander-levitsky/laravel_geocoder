<?php
namespace Piro\Geocoder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\Providers\GeocoderServiceProvider;

class TestCase extends Orchestra {
    protected function getPackageProviders($app): array
    {
        return [GeocoderServiceProvider::class];
    }

    /**
     * Установка настроек "на лету" перед запуском тестов
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Настраиваем фейковые ключи, чтобы тесты не лезли в реальный .env родителя
        $app['config']->set('geocoder', [
            'default' => GeoProviders::GIS,
            'providers' => [
                GeoProviders::GIS => ['api_key' => '2GIS_API_KEY'],
                GeoProviders::MAPTILER => ['api_key' => 'MAPTILER_API_KEY'],
            ],
        ]);
    }
}
