<?php

declare(strict_types=1);

namespace Moe\Marketing;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/marketing.php', 'marketing');
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/marketing.php' => config_path('marketing.php'),
            ], 'marketing-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'marketing-migrations');
        }

        $this->registerEventListeners();
    }

    /**
     * @return void
     */
    protected function registerEventListeners(): void
    {
        if (! class_exists('Moe\\Commerce\\Events\\OrderStatusChanged')) {
            return;
        }

        Event::listen(
            'Moe\\Commerce\\Events\\OrderStatusChanged',
            'Moe\\Marketing\\Listeners\\RecognizeCommissionOnOrderCompleted'
        );
    }
}
