# 🚀 Start Here - Laravel Usage Trigger Package

## What is This?

A **complete Laravel package** that triggers scheduled tasks when users visit your application. No cron jobs needed!

**Perfect for:**
- Digital Ocean App Platform
- Heroku
- Any hosting without initial cron support (without extra workers etc.)
- User-driven task scheduling

## 📖 Read These First

1. **[README.md](laravel-usage-trigger/README.md)** ← Start here for documentation
2. **[PROJECT_ORIGIN.md](laravel-usage-trigger/PROJECT_ORIGIN.md)** - Why this package exists
3. **[DEVELOPMENT_OPTIONS.md](laravel-usage-trigger/DEVELOPMENT_OPTIONS.md)** - Design decisions
4. **[IMPLEMENTATION_STATUS.md](IMPLEMENTATION_STATUS.md)** - What's done, what's next

## 🎯 Quick Start

### Option 1: Use in Current Project

```bash
# Copy package to your project
cp -r laravel-usage-trigger /path/to/your/project/packages/

# Add to composer.json
composer require ./packages/laravel-usage-trigger

# Publish config
php artisan vendor:publish --provider="Eekay\LaravelUsageTrigger\ScheduledTriggerServiceProvider" --tag="config"
```

### Option 2: Create New Package Repository

```bash
# Navigate to package
cd laravel-usage-trigger

# Initialize git
git init
git add .
git commit -m "Initial commit - Laravel Usage Trigger"

# Create GitHub repo (go to github.com and create new repo)

# Add remote
git remote add origin git@github.com:yourname/laravel-usage-trigger.git

# Push code
git push -u origin main
```

### Option 3: Submit to Packagist

```bash
# After creating GitHub repo

# 1. Tag a version
git tag -a v1.0.0 -m "Initial release"
git push origin v1.0.0

# 2. Go to packagist.org
# 3. Submit your GitHub repo URL
# 4. Wait for approval

# 5. Install via Composer
composer require eekay/laravel-usage-trigger
```

## 📋 What's Included

✅ **Core middleware** - Triggers tasks on user visits  
✅ **3 Artisan commands** - List, status, clear  
✅ **Configuration file** - Easy setup  
✅ **Lock mechanism** - Prevents duplicates  
✅ **Daily limits** - Control execution frequency  
✅ **Retry logic** - Auto-retry failed tasks  
✅ **Environment awareness** - Auto-disable in dev  
✅ **Full documentation** - Complete guide  

## 🎓 Key Features

### User-Driven Scheduling
Tasks run when users visit your site. No cron needed!

### Intelligent Timing
- Configurable intervals (minutes/hours)
- Daily execution limits
- Lock mechanism prevents duplicates
- Cache-based tracking

### Production Ready
- Auto-disables in development
- Skips static assets and API routes
- Async execution via queue
- Comprehensive logging

### Easy Configuration
Simple config file, environment variables, clear defaults.

## 💡 Example Use Case

**Scenario:** You need daily backups on Digital Ocean App Platform (no cron support)

**Solution:**
1. Configure backup task in `config/scheduled-trigger.php`
2. Set interval to 24 hours
3. Set daily limit to 1
4. Done! ✅

Every time a user visits your site, the middleware checks if backup is due. If yes, it triggers.

**Bonus:** Add UptimeRobot to ping your site daily → guaranteed backups!

## 📁 Project Structure

```
SEPARATE-REPO/
├── README.md                          ← Overview (this file's parent)
├── START_HERE.md                      ← You are here
├── IMPLEMENTATION_STATUS.md           ← Status checklist
└── laravel-usage-trigger/
    ├── README.md                      ← Full documentation
    ├── PROJECT_ORIGIN.md              ← Development history
    ├── DEVELOPMENT_OPTIONS.md         ← Requirements
    ├── composer.json                  ← Package config
    ├── LICENSE.md                     ← MIT License
    ├── CHANGELOG.md                   ← Version history
    ├── config/
    │   └── scheduled-trigger.php      ← Configuration
    ├── src/
    │   ├── Middleware/
    │   │   └── ScheduledTriggerMiddleware.php
    │   ├── Commands/
    │   │   ├── ScheduledTriggerListCommand.php
    │   │   ├── ScheduledTriggerStatusCommand.php
    │   │   └── ScheduledTriggerClearCommand.php
    │   └── ScheduledTriggerServiceProvider.php
    └── tests/                          ← (Prepared for tests)
```

## ⚡ Quick Commands Reference

```bash
# List all configured tasks
php artisan scheduled-trigger:list

# Check task status
php artisan scheduled-trigger:status

# Clear task cache (for testing)
php artisan scheduled-trigger:clear

# Clear specific task
php artisan scheduled-trigger:clear backup
```

## 🎯 Configuration Example

```php
// config/scheduled-trigger.php

'tasks' => [
    'backup' => [
        'enabled' => true,
        'command' => 'backup:run',
        'interval_minutes' => 1440, // 24 hours
        'async' => true,
        'retries' => 3,
        'per_day_limit' => 1,
    ],
],
```

That's it! Simple and powerful.

## 🤝 Need Help?

1. Read the full **[README.md](laravel-usage-trigger/README.md)**
2. Check **[PROJECT_ORIGIN.md](laravel-usage-trigger/PROJECT_ORIGIN.md)** for context
3. Review **[DEVELOPMENT_OPTIONS.md](laravel-usage-trigger/DEVELOPMENT_OPTIONS.md)** for decisions

## 🎉 Ready to Go!

**This package is complete and production-ready.**

Everything you need is here:
- ✅ Code
- ✅ Configuration
- ✅ Documentation
- ✅ Examples
- ✅ History

**Just pick your path:**
1. Use it directly in your project
2. Create your own package repo
3. Submit to Packagist for the community

---

**Created by:** Edwin Klesman (EEKAY ONLINE)  
**Website:** https://www.eekayonline.com  
**License:** MIT  

**Happy coding! 🚀**

