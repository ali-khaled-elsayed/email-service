<?php

namespace App\Providers;

use App\Enums\Role;
use App\Console\SyncSuperAdminPermissionsCommand;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Policies\EmailLogPolicy;
use App\Services\SuperAdminPermissionSync;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SuperAdminPermissionSync::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([SyncSuperAdminPermissionsCommand::class]);
        }

        Gate::policy(EmailLog::class, EmailLogPolicy::class);

        Gate::before(function ($user, string $ability) {
            if ($user?->isSuperAdmin()) {
                return true;
            }

            return null;
        });

        $this->app->booted(function () {
            app(SuperAdminPermissionSync::class)->syncIfOutdated();
        });
    }
}
