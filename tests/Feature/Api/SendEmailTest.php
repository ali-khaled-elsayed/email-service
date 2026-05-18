<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use Database\Seeders\EmailServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(EmailServiceSeeder::class);
    }

    public function test_send_email_requires_app_key(): void
    {
        $response = $this->postJson('/api/emails/send', [
            'to' => ['test@example.com'],
            'subject' => 'Test',
            'html' => '<p>Test</p>',
        ]);

        $response->assertUnauthorized();
    }

    public function test_send_email_queues_successfully(): void
    {
        Queue::fake();

        $response = $this->postJson('/api/emails/send', [
            'to' => ['test@example.com'],
            'subject' => 'Invoice Created',
            'html' => '<h1>Hello</h1>',
            'priority' => 'high',
            'type' => 'transactional',
        ], [
            'X-APP-KEY' => 'construction_app',
        ]);

        $response->assertAccepted()
            ->assertJson([
                'success' => true,
                'message' => 'Email queued successfully',
            ])
            ->assertJsonStructure(['email_log_id']);

        $this->assertDatabaseHas('email_logs', [
            'subject' => 'Invoice Created',
            'status' => EmailStatus::Queued->value,
        ]);
    }

    public function test_idempotency_returns_existing_log(): void
    {
        Queue::fake();

        $payload = [
            'to' => ['test@example.com'],
            'subject' => 'Test',
            'html' => '<p>Test</p>',
            'idempotency_key' => 'unique-key-123',
        ];

        $this->postJson('/api/emails/send', $payload, ['X-APP-KEY' => 'construction_app']);
        $response = $this->postJson('/api/emails/send', $payload, ['X-APP-KEY' => 'construction_app']);

        $this->assertEquals(1, \App\Modules\EmailService\Models\EmailLog::count());
        $response->assertAccepted();
    }

    public function test_get_email_status(): void
    {
        $app = Application::query()->where('app_key', 'construction_app')->first();
        $log = \App\Modules\EmailService\Models\EmailLog::factory()->create([
            'application_id' => $app->id,
            'provider_id' => Provider::first()->id,
        ]);

        $response = $this->getJson("/api/emails/{$log->id}", [
            'X-APP-KEY' => 'construction_app',
        ]);

        $response->assertOk()->assertJsonPath('data.id', $log->id);
    }
}
