<?php


use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\Geocoder;
use Piro\Geocoder\Tests\TestCase;

class GisProviderTest extends TestCase
{

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config(['geocoder.default' => GeoProviders::GIS]);
    }

    /** @test */
    public function common_test()
    {

        $geocoder = new Geocoder();

        $cases = [
            [[45.585754, 32.841176], GeoProviders::GIS],
            [[35.354269, 33.604686], GeoProviders::MAPTILER],
            [[41.749385, 44.767585], GeoProviders::MAPTILER], // Тбилиси, посёлок Вашлиджвари
            [[45.155335, 39.062756], GeoProviders::GIS], // Краснодар, Вишнёвая улица, 256/7
            [[45.364612, 39.280623], GeoProviders::GIS], // Краснодар, битый адрес 2гис
            [[45.668667, 39.641236], GeoProviders::GIS], // Краснодар, ебеня
            [[45.608433, 38.965067], GeoProviders::GIS], // Тимашевск, ебеня
            [[43.567278, 39.719217], GeoProviders::GIS], // Сочи, море, геокодер ебнулся
        ];


       $test = $geocoder->reverse(43.556337, 39.769694); //

       //45.364612, 39.280623
       dd($test);

    }
}
