<?php

namespace Eekay\LaravelUsageTrigger;

use Illuminate\Support\ServiceProvider;
use Eekay\LaravelUsageTrigger\Commands\ScheduledTriggerListCommand;
use Eekay\LaravelUsageTrigger\Commands\ScheduledTriggerStatusCommand;
use Eekay\LaravelUsageTrigger\Commands\ScheduledTriggerClearCommand;

class ScheduledTriggerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/scheduled-trigger.php',
            'scheduled-trigger'
        );

        // Register NotificationService as singleton
        $this->app->singleton(\Eekay\LaravelUsageTrigger\Services\NotificationService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../config/scheduled-trigger.php' => config_path('scheduled-trigger.php'),
        ], 'config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ScheduledTriggerListCommand::class,
                ScheduledTriggerStatusCommand::class,
                ScheduledTriggerClearCommand::class,
            ]);
        }
    }
}
