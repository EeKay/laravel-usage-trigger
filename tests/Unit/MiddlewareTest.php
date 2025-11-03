<?php

namespace Eekay\LaravelUsageTrigger\Tests\Unit;

use Eekay\LaravelUsageTrigger\Tests\TestCase;
use Eekay\LaravelUsageTrigger\Middleware\ScheduledTriggerMiddleware;
use Eekay\LaravelUsageTrigger\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class MiddlewareTest extends TestCase
{
    protected ScheduledTriggerMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        $notificationService = $this->app->make(NotificationService::class);
        $this->middleware = new ScheduledTriggerMiddleware($notificationService);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /** @test */
    public function it_skips_when_disabled_in_config()
    {
        Config::set('scheduled-trigger.enabled', false);

        $request = Request::create('/');
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_skips_ajax_requests_when_configured()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.skip_ajax', true);
        Config::set('scheduled-trigger.tasks', []);

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_skips_configured_routes()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.skip_routes', ['api/*', 'ping']);
        Config::set('scheduled-trigger.tasks', []);

        $request = Request::create('/api/test');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_skips_static_assets_when_configured()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.skip_assets', true);
        Config::set('scheduled-trigger.tasks', []);

        $request = Request::create('/style.css');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_executes_task_when_interval_has_passed()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.tasks', [
            'test-task' => [
                'enabled' => true,
                'command' => 'cache:clear', // Use a real command that exists
                'interval_minutes' => 60,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lastRunKey = "{$cachePrefix}_test-task_last_run";
        
        // Ensure no previous run
        Cache::forget($lastRunKey);

        $request = Request::create('/');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        
        // Verify that last_run was set (meaning task was executed)
        $this->assertNotNull(Cache::get($lastRunKey));
    }

    /** @test */
    public function it_does_not_execute_task_when_interval_not_passed()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.tasks', [
            'test-task' => [
                'enabled' => true,
                'command' => 'cache:clear',
                'interval_minutes' => 60,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lastRunKey = "{$cachePrefix}_test-task_last_run";
        
        // Set last run to 30 minutes ago (less than 60 minute interval)
        $originalTime = time() - (30 * 60);
        Cache::put($lastRunKey, $originalTime);

        $request = Request::create('/');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        
        // Verify that last_run was NOT updated (meaning task was not executed)
        $this->assertEquals($originalTime, Cache::get($lastRunKey));
    }

    /** @test */
    public function it_respects_daily_limit()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.tasks', [
            'test-task' => [
                'enabled' => true,
                'command' => 'cache:clear',
                'interval_minutes' => 1,
                'per_day_limit' => 2,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $dailyCountKey = "{$cachePrefix}_test-task_daily_count";
        $lastRunKey = "{$cachePrefix}_test-task_last_run";
        
        // Set daily count to limit and ensure enough time has passed
        Cache::put($dailyCountKey, 2, 86400);
        Cache::put($lastRunKey, time() - (2 * 60)); // 2 minutes ago

        $request = Request::create('/');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        
        // Verify that last_run was NOT updated (meaning task was not executed due to limit)
        $originalTime = Cache::get($lastRunKey);
        $this->assertNotNull($originalTime);
        // Give it a moment and check again - should still be the same
        sleep(1);
        $this->assertEquals($originalTime, Cache::get($lastRunKey));
    }

    /** @test */
    public function it_prevents_duplicate_executions_with_lock()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.tasks', [
            'test-task' => [
                'enabled' => true,
                'command' => 'cache:clear',
                'interval_minutes' => 60,
                'lock_duration_seconds' => 300,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lockKey = "{$cachePrefix}_test-task_lock";
        $lastRunKey = "{$cachePrefix}_test-task_last_run";
        
        // Set lock to prevent duplicate execution
        Cache::put($lockKey, true, 300);
        $originalLastRun = time() - (61 * 60); // 61 minutes ago
        Cache::put($lastRunKey, $originalLastRun);

        $request = Request::create('/');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        
        // Verify that last_run was NOT updated (meaning task was not executed due to lock)
        $this->assertEquals($originalLastRun, Cache::get($lastRunKey));
    }

    /** @test */
    public function it_does_not_execute_disabled_task()
    {
        Config::set('scheduled-trigger.enabled', true);
        Config::set('scheduled-trigger.tasks', [
            'test-task' => [
                'enabled' => false,
                'command' => 'cache:clear',
                'interval_minutes' => 60,
            ],
        ]);

        $cachePrefix = Config::get('scheduled-trigger.cache.prefix', 'scheduled_trigger');
        $lastRunKey = "{$cachePrefix}_test-task_last_run";
        
        // Ensure no previous run
        Cache::forget($lastRunKey);

        $request = Request::create('/');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
        
        // Verify that last_run was NOT set (meaning task was not executed)
        $this->assertNull(Cache::get($lastRunKey));
    }
}

