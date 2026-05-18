<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\EmailService\Enums\ApplicationStatus;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Application> */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'app_key' => Str::random(32),
            'status' => ApplicationStatus::Active,
            'default_provider_id' => Provider::factory(),
            'rate_limit' => 100,
            'settings' => [
                'allowed_email_types' => ['transactional', 'marketing'],
                'webhook_url' => null,
                'routing_rules' => [],
            ],
        ];
    }
}
