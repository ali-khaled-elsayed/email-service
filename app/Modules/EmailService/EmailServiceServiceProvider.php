<?php

declare(strict_types=1);

namespace App\Modules\EmailService;

use App\Modules\EmailService\Providers\Brevo\BrevoProvider;
use App\Modules\EmailService\Providers\Mailgun\MailgunProvider;
use App\Modules\EmailService\Providers\Postmark\PostmarkProvider;
use App\Modules\EmailService\Providers\ProviderFactory;
use App\Modules\EmailService\Providers\Resend\ResendProvider;
use App\Modules\EmailService\Providers\SendGrid\SendGridProvider;
use App\Modules\EmailService\Providers\SES\SesProvider;
use App\Modules\EmailService\Providers\SMTP\SmtpProvider;
use App\Modules\EmailService\Repositories\ApplicationRepository;
use App\Modules\EmailService\Repositories\EmailLogRepository;
use App\Modules\EmailService\Repositories\ProviderRepository;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class EmailServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            base_path('config/email_service.php'),
            'email_service'
        );

        $this->app->singleton(ProviderFactory::class);
        $this->app->singleton(ApplicationRepository::class);
        $this->app->singleton(EmailLogRepository::class);
        $this->app->singleton(ProviderRepository::class);

        $this->app->singleton(SmtpProvider::class);
        $this->app->singleton(SesProvider::class);
        $this->app->singleton(MailgunProvider::class);
        $this->app->singleton(SendGridProvider::class);
        $this->app->singleton(PostmarkProvider::class);
        $this->app->singleton(BrevoProvider::class);
        $this->app->singleton(ResendProvider::class);
        $this->app->singleton(Services\EmailSettingsService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\HealthCheckCommand::class,
                Console\ProcessScheduledEmailsCommand::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('email:health-check')->everyFiveMinutes();
            $schedule->command('email:process-scheduled')->everyMinute();

            try {
                $this->app->make(Services\EmailSettingsService::class)->syncRuntimeConfig();
            } catch (\Throwable) {
                // Table may not exist before migrations.
            }
        });
    }
}
