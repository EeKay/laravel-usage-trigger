<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Scheduled Trigger
    |--------------------------------------------------------------------------
    |
    | Set to true to enable task triggering when users visit the application.
    | Can be overridden with SCHEDULED_TRIGGER_ENABLED environment variable.
    |
    */

    'enabled' => env('SCHEDULED_TRIGGER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Skip Routes
    |--------------------------------------------------------------------------
    |
    | Routes that should skip the trigger check. These routes will not
    | trigger any scheduled tasks.
    |
    */

    'skip_routes' => [
        'api/*',
        'ping',
        'admin/*',
        'platform/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skip AJAX Requests
    |--------------------------------------------------------------------------
    |
    | Whether to skip the trigger check for AJAX requests.
    |
    */

    'skip_ajax' => true,

    /*
    |--------------------------------------------------------------------------
    | Skip Static Assets
    |--------------------------------------------------------------------------
    |
    | Whether to skip the trigger check for static asset requests
    | (CSS, JS, images, fonts, etc.).
    |
    */

    'skip_assets' => true,

    /*
    |--------------------------------------------------------------------------
    | Scheduled Tasks Configuration
    |--------------------------------------------------------------------------
    |
    | Define your scheduled tasks here. Each task will be checked on every
    | request, and executed if the interval has passed.
    |
    | Options:
    | - enabled: Enable/disable this specific task
    | - command: Artisan command to execute
    | - interval_minutes: Minutes between executions
    | - lock_duration_seconds: Lock timeout to prevent duplicates (default: 300)
    | - async: Run asynchronously via queue (default: false)
    | - retries: Max retry attempts on failure (default: 0)
    | - per_day_limit: Max executions per day (default: null, unlimited)
    | - parameters: Additional command parameters
    |
    */

    'tasks' => [
        'example-task' => [
            'enabled' => false,
            'command' => 'backup:run',
            'interval_minutes' => 1440, // 24 hours
            'lock_duration_seconds' => 300,
            'async' => true,
            'retries' => 3,
            'per_day_limit' => 1,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications Configuration
    |--------------------------------------------------------------------------
    |
    | Configure notifications for task execution events.
    |
    */

    'notifications' => [
        'enabled' => true,

        'channels' => ['log'], // Options: log, slack, mail

        'notify_on' => [
            'success' => true,
            'failure' => true,
            'retry' => false,
        ],

        'slack' => [
            'webhook_url' => env('SLACK_WEBHOOK_URL'),
            'channel' => null,
        ],

        'mail' => [
            'to' => env('MAIL_TO', null),
            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Laravel Usage Trigger'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Cache settings for task tracking and locking.
    |
    */

    'cache' => [
        'prefix' => 'scheduled_trigger',
        'store' => null, // Use default cache store
    ],

];

