<?php

namespace Eekay\LaravelUsageTrigger\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ScheduledTriggerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip trigger check for certain routes to avoid performance impact
        if ($this->shouldSkipRequest($request)) {
            return $next($request);
        }

        // Check if scheduled trigger should run
        if (!$this->shouldRunScheduledTrigger()) {
            return $next($request);
        }

        try {
            $this->checkAndTriggerTasks();
        } catch (\Exception $e) {
            Log::error('Scheduled trigger failed: ' . $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Check and trigger configured tasks
     */
    private function checkAndTriggerTasks(): void
    {
        $tasks = config('scheduled-trigger.tasks', []);

        foreach ($tasks as $taskName => $taskConfig) {
            if (!$taskConfig['enabled'] ?? false) {
                continue;
            }

            $this->triggerTaskIfNeeded($taskName, $taskConfig);
        }
    }

    /**
     * Trigger a task if the interval has passed
     */
    private function triggerTaskIfNeeded(string $taskName, array $config): void
    {
        $cachePrefix = config('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lastRunKey = "{$cachePrefix}_{$taskName}_last_run";
        $lockKey = "{$cachePrefix}_{$taskName}_lock";
        $dailyCountKey = "{$cachePrefix}_{$taskName}_daily_count";

        $lastRun = Cache::get($lastRunKey);
        $interval = ($config['interval_minutes'] ?? 60) * 60;

        // Check if it's time for a new execution
        if ($this->shouldExecuteTask($lastRun, $interval, $config)) {
            // Check daily limit
            if ($this->hasExceededDailyLimit($dailyCountKey, $config)) {
                return;
            }

            // Use a lock to prevent multiple simultaneous executions
            if (!Cache::has($lockKey)) {
                Cache::put($lockKey, true, $config['lock_duration_seconds'] ?? 300);

                try {
                    $this->executeTask($taskName, $config);

                    // Update last run time
                    Cache::put($lastRunKey, time());

                    // Increment daily count
                    if (isset($config['per_day_limit'])) {
                        $this->incrementDailyCount($dailyCountKey);
                    }

                } catch (\Exception $e) {
                    Log::error("Task '{$taskName}' execution failed: " . $e->getMessage());

                    // Handle retries
                    if (($config['retries'] ?? 0) > 0) {
                        $this->handleRetry($taskName, $config, $e);
                    }
                } finally {
                    Cache::forget($lockKey);
                }
            }
        }
    }

    /**
     * Check if task should execute based on timing
     */
    private function shouldExecuteTask($lastRun, int $interval, array $config): bool
    {
        if (!$lastRun) {
            return true; // First run
        }

        return (time() - $lastRun) > $interval;
    }

    /**
     * Check if daily limit has been exceeded
     */
    private function hasExceededDailyLimit(string $dailyCountKey, array $config): bool
    {
        if (!isset($config['per_day_limit'])) {
            return false; // No limit
        }

        $count = Cache::get($dailyCountKey, 0);
        return $count >= $config['per_day_limit'];
    }

    /**
     * Increment daily execution count
     */
    private function incrementDailyCount(string $dailyCountKey): void
    {
        $count = Cache::get($dailyCountKey, 0);

        // Reset count at start of new day
        $today = date('Y-m-d');
        $lastDate = Cache::get($dailyCountKey . '_date', $today);

        if ($today !== $lastDate) {
            $count = 0;
        }

        Cache::put($dailyCountKey, $count + 1, 86400); // 24 hours
        Cache::put($dailyCountKey . '_date', $today, 86400);
    }

    /**
     * Execute the task command
     */
    private function executeTask(string $taskName, array $config): void
    {
        $command = $config['command'];
        $parameters = $config['parameters'] ?? [];

        Log::info("Executing scheduled task: {$taskName}", [
            'command' => $command,
            'parameters' => $parameters,
        ]);

        // Run backup asynchronously using queue if available
        if (($config['async'] ?? false) && config('queue.default') !== 'sync') {
            dispatch(function () use ($command, $parameters) {
                Artisan::call($command, $parameters);
            })->afterResponse();
        } else {
            // Fallback to immediate execution
            Artisan::call($command, $parameters);
        }
    }

    /**
     * Handle task retry logic
     */
    private function handleRetry(string $taskName, array $config, \Exception $e): void
    {
        $cachePrefix = config('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $retryKey = "{$cachePrefix}_{$taskName}_retries";

        $retries = Cache::get($retryKey, 0);
        $maxRetries = $config['retries'] ?? 0;

        if ($retries < $maxRetries) {
            Cache::put($retryKey, $retries + 1, 3600); // 1 hour expiry

            Log::warning("Retrying task '{$taskName}', attempt " . ($retries + 1) . "/{$maxRetries}");

            // Retry after a short delay
            sleep(5);
            $this->executeTask($taskName, $config);
        } else {
            Log::error("Task '{$taskName}' failed after {$maxRetries} retries");
            Cache::forget($retryKey);
        }
    }

    /**
     * Determine if scheduled trigger should run based on environment
     */
    private function shouldRunScheduledTrigger(): bool
    {
        // Check environment variable first (allows overriding local/development)
        $enabled = env('SCHEDULED_TRIGGER_ENABLED');

        // If explicitly disabled, skip
        if ($enabled === 'false' || $enabled === false || $enabled === '0') {
            return false;
        }

        // If explicitly enabled, run regardless of environment
        if ($enabled === 'true' || $enabled === true || $enabled === '1') {
            return true;
        }

        // Default behavior: Skip in local/development/testing environments
        if (app()->environment(['local', 'development', 'testing'])) {
            return false;
        }

        // Default: enabled in production (when env var is not set)
        return true;
    }

    /**
     * Determine if request should skip trigger check
     */
    private function shouldSkipRequest(Request $request): bool
    {
        // Skip for configured routes
        $skipRoutes = config('scheduled-trigger.skip_routes', []);
        foreach ($skipRoutes as $route) {
            if ($request->is($route)) {
                return true;
            }
        }

        // Skip for AJAX requests
        if (config('scheduled-trigger.skip_ajax', true) && $request->ajax()) {
            return true;
        }

        // Skip for static assets
        if (config('scheduled-trigger.skip_assets', true)) {
            if ($request->is('*.css') ||
                $request->is('*.js') ||
                $request->is('*.png') ||
                $request->is('*.jpg') ||
                $request->is('*.jpeg') ||
                $request->is('*.gif') ||
                $request->is('*.ico') ||
                $request->is('*.svg') ||
                $request->is('*.woff') ||
                $request->is('*.woff2') ||
                $request->is('*.ttf') ||
                $request->is('*.eot')) {
                return true;
            }
        }

        return false;
    }
}

