<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Middleware;

use App\Modules\EmailService\Repositories\ApplicationRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApplication
{
    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $appKey = $request->header('X-APP-KEY');

        if (! $appKey) {
            return response()->json([
                'success' => false,
                'message' => 'X-APP-KEY header is required.',
            ], 401);
        }

        $application = $this->applicationRepository->findByAppKey($appKey);

        if (! $application || ! $application->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive application key.',
            ], 401);
        }

        $request->attributes->set('application', $application);

        return $next($request);
    }
}
