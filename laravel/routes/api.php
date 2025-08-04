<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\LeaderBoardController;
use App\Http\Controllers\Api\QuickWinsController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. They handle all JSON API requests
| from the Vue.js frontend.
|
*/

// All API routes require authentication
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Leaderboard
    Route::get('/leaderboard', [LeaderBoardController::class, 'index'])
        ->name('api.leaderboard');
    
    // Users
    Route::prefix('users')->name('api.users.')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
    });
    
    // Current User
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/', [UsersController::class, 'show'])->name('show');
        Route::patch('/', [UsersController::class, 'update'])->name('update');
        Route::get('/profile', [UsersController::class, 'profile'])->name('profile');
    });
    
    // Shop
    Route::prefix('shop')->name('api.shop.')->group(function () {
        Route::get('/products', [ShopController::class, 'products'])->name('products');
        Route::post('/purchase', [ShopController::class, 'purchase'])->name('purchase');
    });
    
    // Collection
    Route::get('/collection', [CollectionController::class, 'index'])
        ->name('api.collection');
    
    // Quick Wins
    Route::get('/quick-wins', [QuickWinsController::class, 'index'])
        ->name('api.quick-wins');
});