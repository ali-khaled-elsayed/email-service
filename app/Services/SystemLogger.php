<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class SystemLogger
{
    public static function info(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }

    public static function logException(string $operation, Throwable $exception, array $context = []): void
    {
        Log::error($operation, array_merge($context, [
            'exception' => $exception->getMessage(),
            'exception_class' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]));
    }
}
