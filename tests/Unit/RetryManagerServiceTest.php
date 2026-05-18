<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Services\RetryManagerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetryManagerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Modules\EmailService\Models\EmailSetting::instance();
    }

    public function test_should_retry_respects_max_attempts(): void
    {
        $service = app(RetryManagerService::class);
        $log = EmailLog::factory()->make(['retry_count' => 5]);

        $this->assertFalse($service->shouldRetry($log, true));
    }

    public function test_non_retryable_errors_are_rejected(): void
    {
        $service = app(RetryManagerService::class);
        $log = EmailLog::factory()->make(['retry_count' => 0]);

        $this->assertFalse($service->shouldRetry($log, false));
    }

    public function test_retry_delay_uses_config(): void
    {
        $service = app(RetryManagerService::class);

        $this->assertEquals(60, $service->getDelay(1));
        $this->assertEquals(300, $service->getDelay(2));
    }
}
