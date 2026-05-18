<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Controllers;

use App\Modules\EmailService\Enums\EmailStatus;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Repositories\EmailLogRepository;
use App\Modules\EmailService\Services\EmailMetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class TrackingController extends Controller
{
    public function open(int $emailLog, EmailLogRepository $repository, EmailMetricsService $metrics): Response
    {
        $log = $repository->findById($emailLog);
        if ($log) {
            $repository->updateStatus($log, EmailStatus::Opened, 'Email opened');
            $metrics->increment('opened', $log);
        }

        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200, ['Content-Type' => 'image/gif']);
    }

    public function click(int $emailLog, Request $request, EmailLogRepository $repository, EmailMetricsService $metrics): \Illuminate\Http\RedirectResponse
    {
        $log = $repository->findById($emailLog);
        $url = base64_decode((string) $request->query('url', ''));

        if ($log) {
            $repository->updateStatus($log, EmailStatus::Clicked, 'Link clicked');
            $metrics->increment('clicked', $log);
        }

        return redirect()->away($url ?: '/');
    }
}
