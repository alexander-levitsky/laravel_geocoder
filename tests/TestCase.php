<?php
namespace Piro\Geocoder\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
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
        // todo убрать ключи
        // Настраиваем фейковые ключи, чтобы тесты не лезли в реальный .env родителя
        $app['config']->set('geocoder.default', 'dadata');
        $app['config']->set('geocoder.providers.dadata.api_key', 'https://dadata.ru');
        $app['config']->set('geocoder.providers.maptiler.api_key', 'https://maptiler.com');
    }
}
