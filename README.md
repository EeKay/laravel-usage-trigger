# Laravel Usage Trigger

[![Latest Version](https://img.shields.io/packagist/v/eekay/laravel-usage-trigger.svg?style=flat-square)](https://packagist.org/packages/eekay/laravel-usage-trigger)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/eekay/laravel-usage-trigger.svg?style=flat-square)](https://packagist.org/packages/eekay/laravel-usage-trigger)

**Trigger Laravel scheduled tasks when users visit your application - Perfect for hosting platforms without cron job support (or that make you pay for extra agents etc. to enable it)**

A Laravel package that executes Artisan commands when users visit your application, based on configurable time intervals. Perfect for Digital Ocean App Platform, Heroku, and other hosting providers that don't initially support traditional cron jobs (without adding extra worker, etc.).

Developed by **Edwin Klesman** of [EEKAY ONLINE](https://www.eekayonline.com)

---

## 🎯 Use Cases

- ✅ **Automatic backups** triggered by user activity
- ✅ **Cache warming** when site gets traffic
- ✅ **Data cleanup** jobs on regular intervals
- ✅ **Report generation** without cron access
- ✅ **Any scheduled task** on platforms without cron

## 🚀 Key Features

- 🔄 **User-driven scheduling** - Tasks run when users visit your site
- ⏱️ **Configurable intervals** - Set minutes or hours between executions
- 🔒 **Lock mechanism** - Prevents duplicate executions
- 🌍 **Environment aware** - Automatically disabled in local/development
- ⚙️ **Easy configuration** - Simple config file and environment variables
- 📊 **Health monitoring** - Built-in command to check task status
- 🔔 **Notifications** - Log, Slack, or email support
- 🚫 **Smart skipping** - Ignores API routes, assets, and AJAX requests
- 💪 **Laravel 10+** - Built for modern Laravel applications

---

## 📋 Requirements

- PHP >= 8.2
- Laravel >= 10.0
- Cache driver (file, redis, or database)

---

## 📦 Installation

Install the package via Composer:

```bash
composer require eekay/laravel-usage-trigger
```

---

## 🔧 Configuration

### Publish Configuration File

```bash
php artisan vendor:publish --provider="Eekay\LaravelUsageTrigger\ScheduledTriggerServiceProvider" --tag="config"
```

This will create `config/scheduled-trigger.php`.

### Environment Variables

Add these to your `.env` file:

```env
# Enable/disable usage trigger (default: false)
SCHEDULED_TRIGGER_ENABLED=true

# Tasks will be configured in config/scheduled-trigger.php
```

---

## ⚙️ Basic Configuration

Open `config/scheduled-trigger.php`:

```php
return [
    // Enable/disable the trigger globally
    'enabled' => env('SCHEDULED_TRIGGER_ENABLED', false),

    // Skip trigger on these routes
    'skip_routes' => ['api/*', 'ping', 'admin/*'],
    
    // Skip trigger on AJAX requests
    'skip_ajax' => true,

    // Define your scheduled tasks
    'tasks' => [
        'backup' => [
            'enabled' => true,
            'command' => 'backup:run',
            'interval_minutes' => 1440, // 24 hours
            'lock_duration_seconds' => 300,
            'async' => true,
            'retries' => 3,
            'per_day_limit' => 3,
        ],
        
        'cache-warm' => [
            'enabled' => true,
            'command' => 'cache:warm',
            'interval_minutes' => 60,
            'parameters' => ['--force'],
        ],
    ],
];
```

### Task Configuration Options

| Option | Type | Required | Default | Description |
|--------|------|----------|---------|-------------|
| `enabled` | bool | Yes | - | Enable/disable this specific task |
| `command` | string | Yes | - | Artisan command to execute |
| `interval_minutes` | int | Yes | - | Minutes between executions |
| `lock_duration_seconds` | int | No | 300 | Lock timeout in seconds |
| `async` | bool | No | false | Run asynchronously via queue |
| `retries` | int | No | 0 | Max retry attempts on failure |
| `per_day_limit` | int | No | null | Max executions per day |
| `parameters` | array | No | [] | Command parameters |

---

## 🔥 Usage

### Register Middleware

Register the middleware in `app/Http/Kernel.php`:

```php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Eekay\LaravelUsageTrigger\Middleware\ScheduledTriggerMiddleware::class,
    ],
];
```

### Example: Backup Task

This package is perfect for triggering backups on hosting platforms without cron support:

```php
// config/scheduled-trigger.php
'tasks' => [
    'backup' => [
        'enabled' => true,
        'command' => 'backup:run',
        'interval_minutes' => 1440, // Run every 24 hours
        'async' => true,
        'per_day_limit' => 1, // Only once per day
    ],
],
```

When a user visits your site, the middleware checks:
- ✅ Is backup enabled? 
- ✅ Has it been 24 hours since last backup?
- ✅ Not exceeded daily limit?
- ✅ No backup already running?

If all conditions are met → Backup starts automatically!

---

## 📊 Monitoring

### Check Task Status

```bash
php artisan scheduled-trigger:status
```

Output:
```
+----------+--------+-------------------------------------+---------------------+
| Task     | Status | Last Run                            | Next Run (approx)   |
+----------+--------+-------------------------------------+---------------------+
| backup   | Active | 2025-10-30 10:00:00                 | 2025-10-31 10:00:00 |
| cleanup  | Active | Never                               | Next user visit     |
+----------+--------+-------------------------------------+---------------------+
```

### List All Configured Tasks

```bash
php artisan scheduled-trigger:list
```

### Clear Task Cache

```bash
php artisan scheduled-trigger:clear
```

Useful for testing or resetting task timers.

---

## 🔔 Notifications

Configure notifications in `config/scheduled-trigger.php`:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['log', 'slack'], // or 'mail'
    
    'notify_on' => [
        'success' => true,
        'failure' => true,
        'retry' => false,
    ],
    
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => '#backups',
    ],
    
    'mail' => [
        'to' => 'admin@example.com',
        'from' => env('MAIL_FROM_ADDRESS'),
    ],
],
```

---

## 🌍 Environment Behavior

**Local/Development:**
- Package is automatically disabled
- Set `SCHEDULED_TRIGGER_ENABLED=true` to test locally

**Production:**
- Enabled by default when `SCHEDULED_TRIGGER_ENABLED` is not set
- Use environment variable to explicitly control

---

## 🎯 Advanced Features

### Per-Day Limits

Prevent tasks from running too frequently:

```php
'backup' => [
    'enabled' => true,
    'command' => 'backup:run',
    'interval_minutes' => 60,
    'per_day_limit' => 3, // Max 3 times per day
],
```

### Retry Logic

Automatically retry failed tasks:

```php
'backup' => [
    'enabled' => true,
    'command' => 'backup:run',
    'retries' => 3, // Try up to 3 times on failure
],
```

### Async Execution

Use queue for non-blocking execution:

```php
'backup' => [
    'enabled' => true,
    'command' => 'backup:run',
    'async' => true, // Run in background queue
],
```

### Custom Parameters

Pass arguments to your commands:

```php
'cleanup' => [
    'enabled' => true,
    'command' => 'cache:clear',
    'parameters' => ['--force'],
],
```

---

## 🧪 Testing

### Temporarily Enable for Testing

```bash
# Enable trigger
php artisan scheduled-trigger:enable test-backup

# Check status
php artisan scheduled-trigger:status

# Disable when done
php artisan scheduled-trigger:disable test-backup
```

---

## 📚 Real-World Examples

### 1. Digital Ocean App Platform Backup

No cron jobs available? Use user traffic to trigger backups:

```php
'tasks' => [
    'database-backup' => [
        'enabled' => true,
        'command' => 'backup:run',
        'interval_minutes' => 1440,
        'async' => true,
        'per_day_limit' => 1,
        'retries' => 3,
    ],
],
```

Combine with [UptimeRobot](https://uptimerobot.com) for guaranteed daily backups!

### 2. Cache Warming

Warm cache when users visit:

```php
'tasks' => [
    'cache-warm' => [
        'enabled' => true,
        'command' => 'route:cache',
        'interval_minutes' => 60,
        'per_day_limit' => 24,
    ],
],
```

### 3. Daily Reports

Generate reports without cron:

```php
'tasks' => [
    'daily-report' => [
        'enabled' => true,
        'command' => 'reports:daily',
        'interval_minutes' => 2880, // 48 hours
        'per_day_limit' => 1,
    ],
],
```

---

## 🔒 Security

- Tasks are only executed in production environment by default
- Lock mechanism prevents race conditions
- Configurable route skipping for sensitive endpoints
- Environment-based access control

---

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 👤 Author

**Edwin Klesman**

- Website: [www.eekayonline.com](https://www.eekayonline.com)
- Email: online@eekay.nl

---

## 🙏 Acknowledgments

- Inspired by the need for scheduled tasks on Digital Ocean App Platform
- Built with ❤️ for the Laravel community

---

## 📖 Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

---

**Made with ❤️ by [EEKAY ONLINE](https://www.eekayonline.com)**

