<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'application_id',
        'name',
        'slug',
        'subject',
        'html_template',
        'variables',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected static function newFactory(): \Database\Factories\EmailTemplateFactory
    {
        return \Database\Factories\EmailTemplateFactory::new();
    }
}
