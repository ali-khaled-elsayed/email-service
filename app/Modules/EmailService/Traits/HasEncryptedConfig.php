<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasEncryptedConfig
{
    public function getConfigAttribute(?string $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        try {
            $decrypted = Crypt::decryptString($value);

            return json_decode($decrypted, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
    }

    public function setConfigAttribute(array|string|null $value): void
    {
        if (is_array($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        }

        $this->attributes['config'] = $value !== null && $value !== ''
            ? Crypt::encryptString((string) $value)
            : null;
    }
}
