<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => '404 potato not found.',
    ]);
});

Route::post('/chat', [ChatController::class, 'store']);
