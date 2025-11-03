<?php

namespace Eekay\LaravelUsageTrigger\Tests\Feature;

use Eekay\LaravelUsageTrigger\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class CommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function list_command_shows_configured_tasks()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
                'async' => true,
                'retries' => 3,
                'per_day_limit' => 1,
            ],
            'cleanup' => [
                'enabled' => false,
                'command' => 'cache:clear',
                'interval_minutes' => 60,
            ],
        ]);

        $this->artisan('scheduled-trigger:list')
            ->expectsOutput('Configured Scheduled Tasks:')
            ->expectsTable(
                ['Task', 'Command', 'Interval', 'Enabled', 'Async', 'Retries', 'Daily Limit'],
                [
                    ['backup', 'backup:run', '1440 minutes', '✓', 'Yes', '3', '1'],
                    ['cleanup', 'cache:clear', '60 minutes', '✗', 'No', '0', 'Unlimited'],
                ]
            )
            ->assertExitCode(0);
    }

    /** @test */
    public function list_command_shows_message_when_no_tasks()
    {
        Config::set('scheduled-trigger.tasks', []);

        $this->artisan('scheduled-trigger:list')
            ->expectsOutput('No scheduled tasks configured.')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_shows_all_tasks_status()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lastRunKey = "{$cachePrefix}_backup_last_run";
        Cache::put($lastRunKey, time() - 7200); // 2 hours ago

        $this->artisan('scheduled-trigger:status')
            ->expectsOutput('Scheduled Trigger Tasks Status:')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_shows_specific_task_status()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
                'async' => true,
            ],
        ]);

        $this->artisan('scheduled-trigger:status backup')
            ->expectsOutput('Status for task: backup')
            ->assertExitCode(0);
    }

    /** @test */
    public function status_command_shows_error_for_nonexistent_task()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
            ],
        ]);

        $this->artisan('scheduled-trigger:status nonexistent')
            ->expectsOutputToContain("Task 'nonexistent' not found.")
            ->assertExitCode(0);
    }

    /** @test */
    public function clear_command_clears_specific_task_cache()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        
        // Set some cache values
        Cache::put("{$cachePrefix}_backup_last_run", time());
        Cache::put("{$cachePrefix}_backup_lock", true);
        Cache::put("{$cachePrefix}_backup_daily_count", 5);

        $this->artisan('scheduled-trigger:clear backup')
            ->expectsOutput('Cache cleared for task: backup')
            ->assertExitCode(0);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("{$cachePrefix}_backup_last_run"));
        $this->assertFalse(Cache::has("{$cachePrefix}_backup_lock"));
        $this->assertFalse(Cache::has("{$cachePrefix}_backup_daily_count"));
    }

    /** @test */
    public function clear_command_clears_all_tasks_cache()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
            ],
            'cleanup' => [
                'enabled' => true,
                'command' => 'cache:clear',
                'interval_minutes' => 60,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        
        // Set cache values for both tasks
        Cache::put("{$cachePrefix}_backup_last_run", time());
        Cache::put("{$cachePrefix}_cleanup_last_run", time());

        $this->artisan('scheduled-trigger:clear')
            ->expectsConfirmation('Are you sure you want to clear cache for all tasks?', 'yes')
            ->expectsOutput('Cache cleared for all tasks.')
            ->assertExitCode(0);

        // Verify cache is cleared
        $this->assertFalse(Cache::has("{$cachePrefix}_backup_last_run"));
        $this->assertFalse(Cache::has("{$cachePrefix}_cleanup_last_run"));
    }

    /** @test */
    public function clear_command_cancels_when_not_confirmed()
    {
        Config::set('scheduled-trigger.tasks', [
            'backup' => [
                'enabled' => true,
                'command' => 'backup:run',
                'interval_minutes' => 1440,
            ],
        ]);

        $this->artisan('scheduled-trigger:clear')
            ->expectsConfirmation('Are you sure you want to clear cache for all tasks?', 'no')
            ->expectsOutput('Operation cancelled.')
            ->assertExitCode(0);
    }

    /** @test */
    public function clear_command_shows_error_for_nonexistent_task()
    {
        Config::set('scheduled-trigger.tasks', []);

        $this->artisan('scheduled-trigger:clear nonexistent')
            ->expectsOutput("Task 'nonexistent' not found.")
            ->assertExitCode(1);
    }
}

