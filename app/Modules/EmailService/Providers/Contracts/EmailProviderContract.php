<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Providers\Contracts;

use App\Modules\EmailService\DTOs\ProviderSendResultDTO;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Models\Provider;

interface EmailProviderContract
{
    public function send(EmailLog $emailLog, Provider $provider): ProviderSendResultDTO;

    public function validate(Provider $provider): bool;

    public function healthCheck(Provider $provider): ProviderSendResultDTO;

    public function getQuota(Provider $provider): ?int;

    public function supportsAttachments(): bool;
}
