<?php

namespace Moe\Marketing\Tests;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            \Moe\Marketing\MarketingServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('marketing.models.user', \Moe\Marketing\Tests\Stubs\User::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Schema::create('users', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('commerce_orders')) {
            \Illuminate\Support\Facades\Schema::create('commerce_orders', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('commerce_order_items')) {
            \Illuminate\Support\Facades\Schema::create('commerce_order_items', function ($table) {
                $table->id();
                $table->timestamps();
            });
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
