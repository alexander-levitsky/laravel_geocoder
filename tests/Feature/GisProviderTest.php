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
            [[43.468143, 39.927508], GeoProviders::GIS], // Сочи, орел изумруд
            [[43.458144, 39.98185], GeoProviders::GIS], // Сочи, Черешня
            [[43.436821, 40.014988], GeoProviders::GIS], // Сочи, Нижняя шиловка
            [[43.61755, 40.048581], GeoProviders::GIS], // Сочи, Кепша
            [[43.632284, 40.085592], GeoProviders::GIS], // Сочи, чвижепсе
            [[43.678471, 40.203417], GeoProviders::GIS], // Сочи, Эсто садок
            [[41.697314059776915, 44.800898828359706], GeoProviders::MAPTILER], // Грузия, Тбилиси
            [[36.87681158428566, 30.715650695163973], GeoProviders::MAPTILER], // Турция, Муратпаша, Анталья
        ];


       $test = $geocoder->reverse( 45.2141193694119,36.2762797487985);
        //$test = $geocoder->reverse(41.697314059776915, 44.800898828359706);

       dd($test);

    }
}
