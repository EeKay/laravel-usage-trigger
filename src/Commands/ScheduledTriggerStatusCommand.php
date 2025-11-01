<?php

namespace Eekay\LaravelUsageTrigger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScheduledTriggerStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduled-trigger:status {task? : Show status for specific task}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show status of scheduled trigger tasks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cachePrefix = config('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $tasks = config('scheduled-trigger.tasks', []);

        if (empty($tasks)) {
            $this->info('No scheduled tasks configured.');
            return 0;
        }

        $taskName = $this->argument('task');

        if ($taskName) {
            $this->showTaskStatus($taskName, $tasks[$taskName] ?? null, $cachePrefix);
        } else {
            $this->showAllTasksStatus($tasks, $cachePrefix);
        }

        return 0;
    }

    /**
     * Show status for a specific task
     */
    private function showTaskStatus(?string $taskName, ?array $taskConfig, string $cachePrefix): void
    {
        if (!$taskConfig) {
            $this->error("Task '{$taskName}' not found.");
            return;
        }

        $lastRunKey = "{$cachePrefix}_{$taskName}_last_run";
        $lockKey = "{$cachePrefix}_{$taskName}_lock";
        $dailyCountKey = "{$cachePrefix}_{$taskName}_daily_count";

        $lastRun = Cache::get($lastRunKey);
        $isLocked = Cache::has($lockKey);
        $dailyCount = Cache::get($dailyCountKey, 0);

        $this->info("Status for task: {$taskName}");
        $this->newLine();

        $this->line("Enabled: " . (($taskConfig['enabled'] ?? false) ? '✓ Yes' : '✗ No'));
        $this->line("Command: " . ($taskConfig['command'] ?? 'N/A'));
        $this->line("Interval: " . ($taskConfig['interval_minutes'] ?? 0) . " minutes");
        $this->line("Async: " . (($taskConfig['async'] ?? false) ? 'Yes' : 'No'));
        $this->line("Last Run: " . ($lastRun ? date('Y-m-d H:i:s', $lastRun) : 'Never'));
        $this->line("Currently Running: " . ($isLocked ? 'Yes' : 'No'));
        $this->line("Daily Executions: " . $dailyCount . " / " . ($taskConfig['per_day_limit'] ?? 'Unlimited'));

        if ($lastRun) {
            $interval = ($taskConfig['interval_minutes'] ?? 0) * 60;
            $nextRun = $lastRun + $interval;
            $this->line("Next Run: " . date('Y-m-d H:i:s', $nextRun));
        }
    }

    /**
     * Show status for all tasks
     */
    private function showAllTasksStatus(array $tasks, string $cachePrefix): void
    {
        $this->info('Scheduled Trigger Tasks Status:');
        $this->newLine();

        $rows = [];
        foreach ($tasks as $taskName => $taskConfig) {
            $lastRunKey = "{$cachePrefix}_{$taskName}_last_run";
            $lockKey = "{$cachePrefix}_{$taskName}_lock";

            $lastRun = Cache::get($lastRunKey);
            $isLocked = Cache::has($lockKey);

            $status = 'Inactive';
            $nextRun = 'N/A';

            if ($taskConfig['enabled'] ?? false) {
                $status = $isLocked ? 'Running' : 'Active';

                if ($lastRun) {
                    $interval = ($taskConfig['interval_minutes'] ?? 0) * 60;
                    $nextRun = date('Y-m-d H:i:s', $lastRun + $interval);
                } else {
                    $nextRun = 'Next user visit';
                }
            }

            $rows[] = [
                'Task' => $taskName,
                'Status' => $status,
                'Last Run' => $lastRun ? date('Y-m-d H:i:s', $lastRun) : 'Never',
                'Next Run' => $nextRun,
            ];
        }

        $this->table(
            ['Task', 'Status', 'Last Run', 'Next Run'],
            $rows
        );
    }
}

