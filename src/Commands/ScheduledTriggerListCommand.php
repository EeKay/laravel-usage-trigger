<?php

namespace Eekay\LaravelUsageTrigger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScheduledTriggerListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduled-trigger:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all configured scheduled trigger tasks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $tasks = config('scheduled-trigger.tasks', []);

        if (empty($tasks)) {
            $this->info('No scheduled tasks configured.');
            return 0;
        }

        $this->info('Configured Scheduled Tasks:');
        $this->newLine();

        $rows = [];
        foreach ($tasks as $taskName => $taskConfig) {
            $rows[] = [
                'Task' => $taskName,
                'Command' => $taskConfig['command'] ?? 'N/A',
                'Interval' => ($taskConfig['interval_minutes'] ?? 0) . ' minutes',
                'Enabled' => ($taskConfig['enabled'] ?? false) ? '✓' : '✗',
                'Async' => ($taskConfig['async'] ?? false) ? 'Yes' : 'No',
                'Retries' => $taskConfig['retries'] ?? 0,
                'Daily Limit' => $taskConfig['per_day_limit'] ?? 'Unlimited',
            ];
        }

        $this->table(
            ['Task', 'Command', 'Interval', 'Enabled', 'Async', 'Retries', 'Daily Limit'],
            $rows
        );

        return 0;
    }
}

