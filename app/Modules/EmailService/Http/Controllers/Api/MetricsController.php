<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Controllers\Api;

use App\Modules\EmailService\Models\Application;
use App\Modules\EmailService\Services\EmailMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class MetricsController extends Controller
{
    public function index(Request $request, EmailMetricsService $metricsService): JsonResponse
    {
        /** @var Application|null $application */
        $application = $request->attributes->get('application');

        return response()->json([
            'success' => true,
            'metrics' => $metricsService->getDashboardMetrics($application?->id),
        ]);
    }
}
