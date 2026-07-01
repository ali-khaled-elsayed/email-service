<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\AuthLogEvent;
use App\Services\AuthLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationEvents
{
    public function __construct(
        private readonly AuthLogService $authLogService,
    ) {}

    public function handleLogin(Login $event): void
    {
        if (! $event->user instanceof \App\Models\User) {
            return;
        }

        $this->authLogService->record(
            event: AuthLogEvent::Login,
            user: $event->user,
            metadata: ['guard' => $event->guard],
        );
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user instanceof \App\Models\User) {
            return;
        }

        $this->authLogService->record(
            event: AuthLogEvent::Logout,
            user: $event->user,
            metadata: ['guard' => $event->guard],
        );
    }

    public function handleFailed(Failed $event): void
    {
        $this->authLogService->record(
            event: AuthLogEvent::Failed,
            email: is_string($event->credentials['email'] ?? null) ? $event->credentials['email'] : null,
            metadata: ['guard' => $event->guard],
        );
    }

    public function handleLockout(Lockout $event): void
    {
        $this->authLogService->record(
            event: AuthLogEvent::Lockout,
            email: $event->request->input('email'),
            request: $event->request,
        );
    }
}
