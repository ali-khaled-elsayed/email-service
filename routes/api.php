<?php

declare(strict_types=1);

use App\Modules\EmailService\Http\Controllers\Api\EmailController;
use App\Modules\EmailService\Http\Controllers\Api\MetricsController;
use App\Modules\EmailService\Http\Controllers\Api\ProviderHealthController;
use App\Modules\EmailService\Http\Controllers\TrackingController;
use App\Modules\EmailService\Http\Middleware\AuthenticateApplication;
use App\Modules\EmailService\Http\Middleware\LogApiRequest;
use Illuminate\Support\Facades\Route;

Route::middleware(['signed'])->group(function () {
    Route::get('/track/open/{emailLog}', [TrackingController::class, 'open'])->name('email.track.open');
    Route::get('/track/click/{emailLog}', [TrackingController::class, 'click'])->name('email.track.click');
});

Route::middleware([
    AuthenticateApplication::class,
    LogApiRequest::class,
    'throttle:'.config('email_service.rate_limit.per_minute').',1',
])->group(function () {
    Route::post('/emails/send', [EmailController::class, 'send']);
    Route::post('/emails/schedule', [EmailController::class, 'schedule']);
    Route::post('/emails/bulk', [EmailController::class, 'bulk']);
    Route::get('/emails/{id}', [EmailController::class, 'show']);
    Route::post('/emails/{id}/retry', [EmailController::class, 'retry']);
    Route::post('/emails/{id}/cancel', [EmailController::class, 'cancel']);
    Route::get('/providers/health', [ProviderHealthController::class, 'index']);
    Route::get('/metrics', [MetricsController::class, 'index']);
});
