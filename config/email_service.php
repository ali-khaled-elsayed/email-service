<?php

declare(strict_types=1);

return [

    'max_attempts' => (int) env('EMAIL_SERVICE_MAX_ATTEMPTS', 5),

    'retry_delays' => [
        1 => (int) env('EMAIL_RETRY_DELAY_1', 60),
        2 => (int) env('EMAIL_RETRY_DELAY_2', 300),
        3 => (int) env('EMAIL_RETRY_DELAY_3', 900),
        4 => (int) env('EMAIL_RETRY_DELAY_4', 1800),
        5 => (int) env('EMAIL_RETRY_DELAY_5', 3600),
    ],

    'queues' => [
        'high' => 'emails-high',
        'default' => 'emails-default',
        'low' => 'emails-low',
        'bulk' => 'emails-bulk',
        'retry' => 'emails-retry',
    ],

    'rate_limit' => [
        'per_minute' => (int) env('EMAIL_API_RATE_LIMIT', 120),
    ],

    'attachments' => [
        'disk' => env('EMAIL_ATTACHMENTS_DISK', 'local'),
        'path' => 'email-attachments',
        'max_size_kb' => (int) env('EMAIL_ATTACHMENT_MAX_KB', 10240),
    ],

    'tracking' => [
        'enabled' => (bool) env('EMAIL_TRACKING_ENABLED', true),
        'pixel_path' => '/track/open',
        'click_path' => '/track/click',
    ],

    'health_check' => [
        'interval_minutes' => (int) env('EMAIL_HEALTH_CHECK_INTERVAL', 5),
        'failure_threshold' => (int) env('EMAIL_HEALTH_FAILURE_THRESHOLD', 3),
    ],

];
