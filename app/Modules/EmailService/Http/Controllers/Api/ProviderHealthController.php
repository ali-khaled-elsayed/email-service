<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Controllers\Api;

use App\Modules\EmailService\Services\ProviderHealthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class ProviderHealthController extends Controller
{
    public function index(ProviderHealthService $healthService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'providers' => $healthService->getAllHealth(),
        ]);
    }
}
