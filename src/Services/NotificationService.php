<?php

namespace Eekay\LaravelUsageTrigger\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    /**
     * Send notification for task event
     *
     * @param string $taskName
     * @param string $event (success, failure, retry)
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notify(string $taskName, string $event, string $message, array $context = []): void
    {
        $config = config('scheduled-trigger.notifications', []);

        // Check if notifications are enabled
        if (!($config['enabled'] ?? true)) {
            return;
        }

        // Check if we should notify for this event
        $notifyOn = $config['notify_on'] ?? [];
        if (!($notifyOn[$event] ?? false)) {
            return;
        }

        $channels = $config['channels'] ?? ['log'];
        
        foreach ($channels as $channel) {
            try {
                $this->sendToChannel($channel, $taskName, $event, $message, $context, $config);
            } catch (\Exception $e) {
                // Log notification failures but don't break the main flow
                Log::warning("Failed to send notification via {$channel}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send notification to a specific channel
     *
     * @param string $channel
     * @param string $taskName
     * @param string $event
     * @param string $message
     * @param array $context
     * @param array $config
     * @return void
     */
    protected function sendToChannel(string $channel, string $taskName, string $event, string $message, array $context, array $config): void
    {
        switch ($channel) {
            case 'log':
                $this->sendLogNotification($taskName, $event, $message, $context);
                break;
            
            case 'slack':
                $this->sendSlackNotification($taskName, $event, $message, $context, $config['slack'] ?? []);
                break;
            
            case 'mail':
                $this->sendMailNotification($taskName, $event, $message, $context, $config['mail'] ?? []);
                break;
        }
    }

    /**
     * Send log notification
     *
     * @param string $taskName
     * @param string $event
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function sendLogNotification(string $taskName, string $event, string $message, array $context): void
    {
        $level = $this->getLogLevel($event);
        $prefix = "[ScheduledTrigger] [{$taskName}] [{$event}]";

        Log::{$level}("{$prefix} {$message}", $context);
    }

    /**
     * Send Slack notification
     *
     * @param string $taskName
     * @param string $event
     * @param string $message
     * @param array $context
     * @param array $slackConfig
     * @return void
     */
    protected function sendSlackNotification(string $taskName, string $event, string $message, array $context, array $slackConfig): void
    {
        $webhookUrl = $slackConfig['webhook_url'] ?? null;

        if (!$webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return;
        }

        $color = $this->getSlackColor($event);
        $icon = $this->getSlackIcon($event);
        
        $payload = [
            'text' => "Scheduled Task {$event}: {$taskName}",
            'attachments' => [
                [
                    'color' => $color,
                    'fields' => [
                        [
                            'title' => 'Task',
                            'value' => $taskName,
                            'short' => true,
                        ],
                        [
                            'title' => 'Event',
                            'value' => ucfirst($event),
                            'short' => true,
                        ],
                        [
                            'title' => 'Message',
                            'value' => $message,
                            'short' => false,
                        ],
                    ],
                    'footer' => 'Laravel Usage Trigger',
                    'ts' => time(),
                ],
            ],
        ];

        // Add channel if specified
        if (!empty($slackConfig['channel'])) {
            $payload['channel'] = $slackConfig['channel'];
        }

        try {
            Http::post($webhookUrl, $payload);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Send mail notification
     *
     * @param string $taskName
     * @param string $event
     * @param string $message
     * @param array $context
     * @param array $mailConfig
     * @return void
     */
    protected function sendMailNotification(string $taskName, string $event, string $message, array $context, array $mailConfig): void
    {
        $to = $mailConfig['to'] ?? null;

        if (!$to) {
            Log::warning('Mail notification recipient not configured');
            return;
        }

        $from = $mailConfig['from'] ?? [
            'address' => 'noreply@example.com',
            'name' => 'Laravel Usage Trigger',
        ];

        $subject = "Scheduled Task {$event}: {$taskName}";
        $body = $this->buildMailBody($taskName, $event, $message, $context);

        try {
            Mail::raw($body, function ($mail) use ($to, $from, $subject) {
                $mail->to($to)
                     ->from($from['address'], $from['name'])
                     ->subject($subject);
            });
        } catch (\Exception $e) {
            Log::error('Failed to send mail notification: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Build mail body
     *
     * @param string $taskName
     * @param string $event
     * @param string $message
     * @param array $context
     * @return string
     */
    protected function buildMailBody(string $taskName, string $event, string $message, array $context): string
    {
        $body = "Scheduled Task Notification\n";
        $body .= "==========================\n\n";
        $body .= "Task: {$taskName}\n";
        $body .= "Event: " . ucfirst($event) . "\n";
        $body .= "Message: {$message}\n";
        $body .= "Time: " . date('Y-m-d H:i:s') . "\n\n";

        if (!empty($context)) {
            $body .= "Additional Context:\n";
            foreach ($context as $key => $value) {
                $body .= "- {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
            }
        }

        return $body;
    }

    /**
     * Get log level based on event
     *
     * @param string $event
     * @return string
     */
    protected function getLogLevel(string $event): string
    {
        return match ($event) {
            'success' => 'info',
            'failure' => 'error',
            'retry' => 'warning',
            default => 'info',
        };
    }

    /**
     * Get Slack color based on event
     *
     * @param string $event
     * @return string
     */
    protected function getSlackColor(string $event): string
    {
        return match ($event) {
            'success' => 'good',
            'failure' => 'danger',
            'retry' => 'warning',
            default => '#36a64f',
        };
    }

    /**
     * Get Slack icon based on event
     *
     * @param string $event
     * @return string
     */
    protected function getSlackIcon(string $event): string
    {
        return match ($event) {
            'success' => ':white_check_mark:',
            'failure' => ':x:',
            'retry' => ':warning:',
            default => ':information_source:',
        };
    }
}

