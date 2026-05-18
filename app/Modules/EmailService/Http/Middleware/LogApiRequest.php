<?php

declare(strict_types=1);

namespace App\Modules\EmailService\Http\Middleware;

use App\Modules\EmailService\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $duration = (int) ((microtime(true) - $start) * 1000);

        ApiRequestLog::query()->create([
            'application_id' => $request->attributes->get('application')?->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip_address' => $request->ip(),
            'request_body' => $request->except(['html', 'attachments']),
            'response_body' => json_decode($response->getContent(), true),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}
