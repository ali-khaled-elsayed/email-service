<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Services;

use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Services\EmailTrackingService;

class HtmlEmailRendererService
{
    public function __construct(
        private readonly EmailTrackingService $trackingService,
    ) {}

    public function render(EmailLog $emailLog): string
    {
        $html = $emailLog->html ?? '';

        if (config('email_service.tracking.enabled')) {
            $html = $this->trackingService->injectTrackingPixel($html, $emailLog);
        }

        return $this->sanitize($html);
    }

    public function sanitize(string $html): string
    {
        return strip_tags($html, '<p><br><b><i><u><strong><em><a><ul><ol><li><h1><h2><h3><h4><h5><h6><table><tr><td><th><thead><tbody><img><div><span><style>');
    }
}
