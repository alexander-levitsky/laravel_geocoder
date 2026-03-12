<?php

namespace Piro\Geocoder\Providers;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\ServiceProvider;


class GeocoderServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        // Слияние конфига пакета с конфигом приложения
        $this->mergeConfigFrom(
            __DIR__.'/../../config/geocoder.php', 'geocoder'
        );
    }

    public function boot(Kernel $kernel): void {
        // Позволяет пользователю опубликовать конфиг в основную папку config/ приложения
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/geocoder.php' => config_path('geocoder.php'),
            ], 'geocoder-config');
        }
    }

}
