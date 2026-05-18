<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailMetric extends Model
{
    protected $fillable = [
        'date',
        'application_id',
        'provider_id',
        'metric',
        'value',
        'breakdown',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'breakdown' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }
}
