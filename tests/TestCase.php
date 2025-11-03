<?php

namespace Eekay\LaravelUsageTrigger\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Eekay\LaravelUsageTrigger\ScheduledTriggerServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Publish config for testing
        $this->artisan('vendor:publish', [
            '--provider' => ScheduledTriggerServiceProvider::class,
            '--tag' => 'config',
        ]);
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            ScheduledTriggerServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup cache
        $app['config']->set('cache.default', 'array');

        // Setup queue
        $app['config']->set('queue.default', 'sync');
    }
}

