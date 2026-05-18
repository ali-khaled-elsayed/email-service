<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Enums\EmailType;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use App\Modules\EmailService\Services\ProviderResolverService;
use Database\Seeders\EmailServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderResolverServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailServiceSeeder::class);
    }

    public function test_resolves_routing_rule_by_email_type(): void
    {
        $app = Application::query()->where('app_key', 'construction_app')->first();
        $resolver = app(ProviderResolverService::class);

        $dto = new SendEmailDTO(
            to: ['test@example.com'],
            subject: 'Test',
            html: '<p>Test</p>',
            type: EmailType::Transactional,
        );

        $provider = $resolver->resolve($app, $dto);

        $this->assertEquals('smtp_primary', $provider?->slug);
    }

    public function test_resolves_default_provider(): void
    {
        $app = Application::query()->where('app_key', 'construction_app')->first();
        $resolver = app(ProviderResolverService::class);

        $dto = new SendEmailDTO(
            to: ['test@example.com'],
            subject: 'Test',
            html: '<p>Test</p>',
            type: EmailType::Transactional,
        );

        $provider = $resolver->resolve($app, $dto);

        $this->assertNotNull($provider);
    }
}
