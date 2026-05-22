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
                GeoProviders::GIS => ['api_key' => '0607d2f8-36f4-4d40-8d72-734a837d41a9'],
                GeoProviders::MAPTILER => ['api_key' => 'ZWE7e9L3CJGm15BRdXIF'],
            ],
        ]);
    }
}
