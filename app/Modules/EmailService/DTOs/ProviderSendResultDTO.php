<?php

declare(strict_types=1);

namespace App\Modules\EmailService\DTOs;

final readonly class ProviderSendResultDTO
{
    /**
     * @param  array<string, mixed>  $response
     */
    public function __construct(
        public bool $success,
        public ?string $messageId = null,
        public array $response = [],
        public ?string $error = null,
        public bool $retryable = false,
    ) {}
}
