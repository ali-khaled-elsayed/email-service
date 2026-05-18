<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\EmailService\Enums\EmailPriority;
use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Enums\EmailType;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmailLog> */
class EmailLogFactory extends Factory
{
    protected $model = EmailLog::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'status' => EmailStatus::Pending,
            'priority' => EmailPriority::Default,
            'type' => EmailType::Transactional,
            'subject' => fake()->sentence(),
            'to' => [fake()->safeEmail()],
            'html' => '<p>'.fake()->paragraph().'</p>',
            'retry_count' => 0,
        ];
    }
}
