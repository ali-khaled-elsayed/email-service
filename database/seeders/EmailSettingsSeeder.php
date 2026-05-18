<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\EmailService\Models\EmailSetting;
use Illuminate\Database\Seeder;

class EmailSettingsSeeder extends Seeder
{
    public function run(): void
    {
        EmailSetting::query()->delete();

        EmailSetting::query()->create([
            'max_attempts' => (int) config('email_service.max_attempts', 5),
            'retry_delays' => config('email_service.retry_delays', [
                1 => 60,
                2 => 300,
                3 => 900,
                4 => 1800,
                5 => 3600,
            ]),
        ]);
    }
}
