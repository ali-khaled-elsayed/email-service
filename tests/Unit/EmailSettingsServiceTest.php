<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\EmailService\Models\EmailSetting;
use App\Modules\EmailService\Services\EmailSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class EmailSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dynamic_settings_override_config_at_runtime(): void
    {
        $service = app(EmailSettingsService::class);

        $service->update(3, [
            1 => 120,
            2 => 600,
            3 => 1800,
        ]);

        $service->clearCache();
        $service->syncRuntimeConfig();

        $this->assertEquals(3, $service->getMaxAttempts());
        $this->assertEquals(120, $service->getDelay(1));
        $this->assertEquals(600, $service->getDelay(2));
        $this->assertEquals(3, Config::get('email_service.max_attempts'));
        $this->assertEquals(120, Config::get('email_service.retry_delays')[1]);
    }

    public function test_normalize_delays_fills_missing_attempts(): void
    {
        $service = app(EmailSettingsService::class);

        $normalized = $service->normalizeDelays(3, [1 => 100]);

        $this->assertCount(3, $normalized);
        $this->assertEquals(100, $normalized[1]);
    }

    public function test_get_reads_from_database_after_update(): void
    {
        $service = app(EmailSettingsService::class);
        $service->update(2, [1 => 30, 2 => 90]);

        $this->assertDatabaseHas('email_settings', ['max_attempts' => 2]);
        $this->assertEquals(1, EmailSetting::query()->count());

        $service->clearCache();

        $this->assertEquals(2, $service->getMaxAttempts());
        $this->assertEquals(30, $service->getDelay(1));
    }
}
