<?php

use App\Http\Controllers\SlackController;
use App\Http\Middleware\VerifyServiceToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => '404 potato not found.',
    ]);
});

Route::post('/events', [SlackController::class, 'event'])
    ->middleware(VerifyServiceToken::class);
