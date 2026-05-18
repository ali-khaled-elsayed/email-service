<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmailTemplate> */
class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'application_id' => Application::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'subject' => 'Hello {{name}}',
            'html_template' => '<h1>Hello {{name}}</h1><p>{{message}}</p>',
            'variables' => ['name', 'message'],
        ];
    }
}
