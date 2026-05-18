<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\EmailService\Enums\HealthStatus;
use App\Modules\EmailService\Enums\ProviderStatus;
use App\Modules\EmailService\Enums\ProviderType;
use App\Modules\EmailService\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Provider> */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'type' => ProviderType::Smtp,
            'status' => ProviderStatus::Active,
            'priority' => fake()->numberBetween(1, 100),
            'config' => [
                'host' => 'smtp.mailtrap.io',
                'port' => 587,
                'encryption' => 'tls',
                'username' => 'test',
                'password' => 'test',
                'from_email' => 'noreply@example.com',
                'from_name' => 'Email Service',
            ],
            'health_status' => HealthStatus::Healthy,
            'quota_limit' => 10000,
            'quota_used' => 0,
            'timeout' => 30,
            'weight' => 1,
        ];
    }
}
