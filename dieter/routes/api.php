<?php

use App\Http\Controllers\SlackController;
use App\Http\Middleware\VerifyServiceToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => '404 potato not found.',
    ]);
});

Route::middleware(VerifyServiceToken::class)->group(function () {
    Route::post('/events', [SlackController::class, 'event']);
});
