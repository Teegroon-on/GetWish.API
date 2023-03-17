<?php

namespace App\Providers;

use App\Providers\SmsAeroApi\SmsAeroApi;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    { }

    /**
     * Register SmsAero service.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(SmsAeroApi::class, function () {
            return new SmsAeroApi();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [SmsAeroApi::class];
    }
}
