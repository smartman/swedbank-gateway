<?php

namespace Smartman\Swedbank;

use Illuminate\Support\ServiceProvider;

class SwedbankGatewayProvider extends ServiceProvider
{

    protected $defer = true;

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/swedbank.php'                                => config_path('swedbank.php'),
            __DIR__ . '/migrations/2018_03_20_084451_swedbank_requests.php' => database_path('/migrations/2018_03_20_084451_swedbank_requests.php'),
            __DIR__ . '/jdigidoc/jdigidoc.cfg'                              => config_path('jdigidoc.cfg'),
            __DIR__ . '/jdigidoc/jdigidoc-sandbox.cfg'                      => config_path('jdigidoc-sandbox.cfg'),
        ]);
    }

    public function register()
    {
        $this->app->singleton('swedbank', function ($app) {
            return new SwedbankGatewayImplementation($app);
        });
    }

    public function provides()
    {
        return ['swedbank'];
    }
}
