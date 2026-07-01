<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuthLogEvent;
use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuthLogService
{
    public function record(
        AuthLogEvent $event,
        ?User $user = null,
        ?string $email = null,
        ?Request $request = null,
        array $metadata = [],
    ): AuthLog {
        $request ??= request();

        return AuthLog::query()->create([
            'user_id' => $user?->id,
            'email' => $email ?? $user?->email,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }
}
