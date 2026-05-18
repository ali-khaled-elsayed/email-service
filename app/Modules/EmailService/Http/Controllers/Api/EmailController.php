<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Controllers\Api;

use App\Modules\EmailService\Actions\SendEmailAction;
use App\Modules\EmailService\DTOs\SendEmailDTO;
use App\Modules\EmailService\Http\Requests\BulkEmailRequest;
use App\Modules\EmailService\Http\Requests\SendEmailRequest;
use App\Modules\EmailService\Http\Resources\EmailLogResource;
use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Models\EmailLog;
use App\Modules\EmailService\Repositories\EmailLogRepository;
use App\Modules\EmailService\Services\BulkEmailService;
use App\Modules\EmailService\Services\EmailCancellationService;
use App\Modules\EmailService\Services\RetryManagerService;
use App\Modules\EmailService\Services\ScheduledEmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EmailController extends Controller
{
    public function send(SendEmailRequest $request, SendEmailAction $action): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $dto = SendEmailDTO::fromArray($request->validated());
        $emailLog = $action->execute($application, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Email queued successfully',
            'email_log_id' => $emailLog->id,
        ], 202);
    }

    public function schedule(SendEmailRequest $request, ScheduledEmailService $service): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $dto = SendEmailDTO::fromArray($request->validated());
        $emailLog = $service->schedule($application, $dto);

        return response()->json([
            'success' => true,
            'message' => 'Email scheduled successfully',
            'email_log_id' => $emailLog->id,
        ], 202);
    }

    public function bulk(BulkEmailRequest $request, BulkEmailService $service): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $validated = $request->validated();
        $ids = $service->dispatch(
            $application,
            $validated['recipients'],
            $validated,
        );

        return response()->json([
            'success' => true,
            'message' => 'Bulk emails queued successfully',
            'email_log_ids' => $ids->values()->all(),
            'count' => $ids->count(),
        ], 202);
    }

    public function show(int $id, Request $request, EmailLogRepository $repository): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $emailLog = $repository->findById($id);

        if (! $emailLog || $emailLog->application_id !== $application->id) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new EmailLogResource($emailLog),
        ]);
    }

    public function retry(int $id, Request $request, RetryManagerService $retryManager, EmailLogRepository $repository): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $emailLog = $repository->findById($id);

        if (! $emailLog || $emailLog->application_id !== $application->id) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        $retryManager->manualRetry($emailLog);

        return response()->json([
            'success' => true,
            'message' => 'Email retry queued',
            'email_log_id' => $emailLog->id,
        ]);
    }

    public function cancel(int $id, Request $request, EmailCancellationService $cancellation, EmailLogRepository $repository): JsonResponse
    {
        /** @var Application $application */
        $application = $request->attributes->get('application');
        $emailLog = $repository->findById($id);

        if (! $emailLog || $emailLog->application_id !== $application->id) {
            return response()->json(['success' => false, 'message' => 'Email not found.'], 404);
        }

        $cancellation->cancel($emailLog);

        return response()->json([
            'success' => true,
            'message' => 'Email cancelled successfully',
        ]);
    }
}
