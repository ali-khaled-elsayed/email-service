<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class EmailSettingsService
{
    private const CACHE_KEY = 'email_service.settings';

    public function get(): EmailSetting
    {
        if (! $this->tableExists()) {
            return $this->fallbackSetting();
        }

        return Cache::remember(
            self::CACHE_KEY,
            3600,
            fn () => EmailSetting::instance()->fresh(),
        );
    }

    public function getMaxAttempts(): int
    {
        return max(1, (int) $this->get()->max_attempts);
    }

    /**
     * @return array<int, int>
     */
    public function getRetryDelays(): array
    {
        $delays = $this->get()->retry_delays ?? [];

        if ($delays === []) {
            return $this->defaultRetryDelays();
        }

        $normalized = [];
        foreach ($delays as $attempt => $delay) {
            $normalized[(int) $attempt] = (int) $delay;
        }

        ksort($normalized);

        return $normalized;
    }

    public function getDelay(int $attemptNumber): int
    {
        $delays = $this->getRetryDelays();

        if (isset($delays[$attemptNumber])) {
            return (int) $delays[$attemptNumber];
        }

        return (int) (end($delays) ?: 60);
    }

    /**
     * @param  array<int, int>  $retryDelays
     */
    public function update(int $maxAttempts, array $retryDelays): EmailSetting
    {
        $settings = EmailSetting::instance();
        $settings->update([
            'max_attempts' => max(1, $maxAttempts),
            'retry_delays' => $this->normalizeDelays($maxAttempts, $retryDelays),
        ]);

        $this->clearCache();
        $this->syncRuntimeConfig();

        return $settings->fresh();
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function syncRuntimeConfig(): void
    {
        if (! $this->tableExists()) {
            return;
        }

        Config::set('email_service.max_attempts', $this->getMaxAttempts());
        Config::set('email_service.retry_delays', $this->getRetryDelays());
    }

    /**
     * @param  array<int, int>  $retryDelays
     * @return array<int, int>
     */
    public function normalizeDelays(int $maxAttempts, array $retryDelays): array
    {
        $normalized = [];

        for ($i = 1; $i <= $maxAttempts; $i++) {
            $normalized[$i] = (int) ($retryDelays[$i] ?? $this->defaultRetryDelays()[$i] ?? end($retryDelays) ?: 60);
        }

        return $normalized;
    }

    /**
     * @return array<int, int>
     */
    public function defaultRetryDelays(): array
    {
        return array_map('intval', config('email_service.retry_delays', [
            1 => 60,
            2 => 300,
            3 => 900,
            4 => 1800,
            5 => 3600,
        ]));
    }

    private function tableExists(): bool
    {
        try {
            return Schema::hasTable('email_settings');
        } catch (\Throwable) {
            return false;
        }
    }

    private function fallbackSetting(): EmailSetting
    {
        $setting = new EmailSetting;
        $setting->max_attempts = (int) config('email_service.max_attempts', 5);
        $setting->retry_delays = config('email_service.retry_delays', $this->defaultRetryDelays());

        return $setting;
    }
}
