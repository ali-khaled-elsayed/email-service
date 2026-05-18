<?php

declare(strict_types=1);

/**
 * Scaffolds the Email Service module files.
 * Run: php scaffold/generate.php
 */

$base = dirname(__DIR__) . '/app/Modules/EmailService';

$dirs = [
    'Actions', 'DTOs', 'Enums', 'Events', 'Exceptions', 'Filament/Resources',
    'Filament/Resources/ApplicationResource/Pages',
    'Filament/Resources/ProviderResource/Pages',
    'Filament/Resources/EmailLogResource/Pages',
    'Filament/Resources/EmailTemplateResource/Pages',
    'Filament/Resources/FailedAttemptResource/Pages',
    'Filament/Widgets', 'Filament/Pages',
    'Http/Controllers/Api', 'Http/Middleware', 'Http/Requests',
    'Http/Resources', 'Jobs', 'Listeners', 'Mail',
    'Models', 'Notifications', 'Policies',
    'Providers/Contracts', 'Providers/SMTP', 'Providers/SES',
    'Providers/Mailgun', 'Providers/SendGrid', 'Providers/Postmark',
    'Providers/Brevo', 'Providers/Resend',
    'Repositories', 'Services', 'Support', 'Traits', 'ValueObjects',
];

foreach ($dirs as $dir) {
    $path = $base . '/' . $dir;
    if (! is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

echo "Directories created.\n";
