<?php

use App\Http\Controllers\EventsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Service Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming requests from external services like the
| potal Go service. They use different authentication mechanisms than
| the web/API routes.
|
*/

// Slack Events (via potal service)
Route::middleware(['verify.potal'])->group(function () {
    Route::post('/events', [EventsController::class, 'handle'])
        ->name('services.events.handle');
});

// Health check endpoint (no auth required)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ]);
})->name('services.health');