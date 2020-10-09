<?php

namespace SanjabTicket;

use Illuminate\Support\ServiceProvider;

class SanjabTicketServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'sanjab-ticket');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sanjab-ticket');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('sanjab-ticket.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sanjab-ticket'),
        ], 'views');

        $this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/sanjab-ticket'),
        ], 'lang');
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'sanjab-ticket');

        $this->app->singleton('sanjab-ticket', function () {
            return new SanjabTicket;
        });
    }
}
