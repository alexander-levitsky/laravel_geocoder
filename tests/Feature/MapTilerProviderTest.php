<?php


use Piro\Geocoder\Contracts\GeoProviders;
use Piro\Geocoder\Geocoder;
use Piro\Geocoder\Tests\TestCase;

class MapTilerProviderTest extends TestCase
{

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);
        config(['geocoder.default' => GeoProviders::MAPTILER]);
    }

    /** @test */
    public function common_test()
    {

        $geocoder = new Geocoder();

        $tmp = $geocoder->reverse(43.4536608160113,39.949578555514815);

        dd($tmp);

    }

}
