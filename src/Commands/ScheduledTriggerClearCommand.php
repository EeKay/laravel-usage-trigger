<?php

namespace Eekay\LaravelUsageTrigger\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScheduledTriggerClearCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduled-trigger:clear {task? : Clear cache for specific task}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear cache for scheduled trigger tasks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $cachePrefix = config('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $tasks = config('scheduled-trigger.tasks', []);

        $taskName = $this->argument('task');

        if ($taskName) {
            // Clear specific task
            if (!isset($tasks[$taskName])) {
                $this->error("Task '{$taskName}' not found.");
                return 1;
            }

            $this->clearTaskCache($taskName, $cachePrefix);
            $this->info("Cache cleared for task: {$taskName}");
        } else {
            // Clear all tasks
            if (!$this->confirm('Are you sure you want to clear cache for all tasks?')) {
                $this->info('Operation cancelled.');
                return 0;
            }

            foreach ($tasks as $task => $config) {
                $this->clearTaskCache($task, $cachePrefix);
            }

            $this->info('Cache cleared for all tasks.');
        }

        return 0;
    }

    /**
     * Clear cache for a specific task
     */
    private function clearTaskCache(string $taskName, string $cachePrefix): void
    {
        Cache::forget("{$cachePrefix}_{$taskName}_last_run");
        Cache::forget("{$cachePrefix}_{$taskName}_lock");
        Cache::forget("{$cachePrefix}_{$taskName}_daily_count");
        Cache::forget("{$cachePrefix}_{$taskName}_daily_count_date");
        Cache::forget("{$cachePrefix}_{$taskName}_retries");
    }
}

