<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = [
        'max_attempts',
        'retry_delays',
    ];

    protected function casts(): array
    {
        return [
            'retry_delays' => 'array',
        ];
    }

    public static function instance(): self
    {
        $setting = static::query()->orderBy('id')->first();

        if ($setting) {
            static::query()->whereKeyNot($setting->getKey())->delete();

            return $setting;
        }

        return static::query()->create([
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
