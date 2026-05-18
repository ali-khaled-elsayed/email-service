<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Repositories;

use App\Modules\EmailService\Models\Application;

class ApplicationRepository
{
    public function findByAppKey(string $appKey): ?Application
    {
        return Application::query()
            ->with(['defaultProvider', 'fallbackProvider'])
            ->where('app_key', $appKey)
            ->first();
    }
}
