<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\EmailService\Enums\ApplicationStatus;
use App\Modules\EmailService\Enums\HealthStatus;
use App\Modules\EmailService\Enums\ProviderStatus;
use App\Modules\EmailService\Enums\ProviderType;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailTemplate;
use App\Modules\EmailService\Models\Provider;
use Illuminate\Database\Seeder;

class EmailServiceSeeder extends Seeder
{
    public function run(): void
    {
        $smtp = Provider::query()->create([
            'name' => 'SMTP Primary',
            'slug' => 'smtp_primary',
            'type' => ProviderType::Smtp,
            'status' => ProviderStatus::Active,
            'priority' => 10,
            'config' => [
                'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => (int) env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'Email Service'),
            ],
            'health_status' => HealthStatus::Healthy,
            'quota_limit' => 50000,
            'timeout' => 30,
            'weight' => 5,
        ]);

        $fallback = Provider::query()->create([
            'name' => 'SMTP Fallback',
            'slug' => 'smtp_fallback',
            'type' => ProviderType::Smtp,
            'status' => ProviderStatus::Active,
            'priority' => 50,
            'config' => [
                'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => (int) env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'from_email' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
                'from_name' => env('MAIL_FROM_NAME', 'Email Service'),
            ],
            'health_status' => HealthStatus::Healthy,
            'quota_limit' => 10000,
            'timeout' => 30,
            'weight' => 1,
        ]);

        $app = Application::query()->create([
            'name' => 'Construction App',
            'app_key' => 'construction_app',
            'status' => ApplicationStatus::Active,
            'default_provider_id' => $smtp->id,
            'fallback_provider_id' => $fallback->id,
            'rate_limit' => 200,
            'settings' => [
                'webhook_url' => null,
                'allowed_email_types' => ['transactional', 'marketing', 'notification'],
                'routing_rules' => [
                    'transactional' => 'smtp_primary',
                    'marketing' => 'smtp_primary',
                ],
                'retry_policy' => [
                    'max_attempts' => 5,
                ],
            ],
        ]);

        EmailTemplate::query()->create([
            'application_id' => $app->id,
            'name' => 'Invoice Created',
            'slug' => 'invoice_created',
            'subject' => 'Invoice #{{invoice_id}} Created',
            'html_template' => '<h1>Hello {{name}}</h1><p>Your invoice #{{invoice_id}} was created.</p>',
            'variables' => ['name', 'invoice_id'],
        ]);
    }
}
