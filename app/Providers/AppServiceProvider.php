<?php

namespace App\Providers;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Policies\EmailLogPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(EmailLog::class, EmailLogPolicy::class);
    }
}
