<?php

use App\Http\Controllers\Auth\SlackLoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TermsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes handle the web interface and authentication flow
|
*/

// Authentication Routes (No auth middleware)
Route::middleware(['guest'])->group(function () {
    Route::get('/login', [SlackLoginController::class, 'login'])->name('login');
    Route::get('/login/mobile', [SlackLoginController::class, 'mobile'])->name('login.mobile');
    Route::get('/start-open-id/{workspace?}', [SlackLoginController::class, 'redirect'])->name('slack.redirect');
    Route::get('/open-id/{workspace?}', [SlackLoginController::class, 'callback'])->name('slack.callback');
});

// Logout (Accessible to authenticated users)
Route::post('/logout', [SlackLoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::get('/logout', [SlackLoginController::class, 'logout']); // Support GET for backward compatibility

// Terms page (No auth required)
Route::get('/terms', [TermsController::class, 'index'])->name('terms');

// Authenticated Routes
Route::middleware(['auth', 'verified'])->group(function () {
    // SPA Routes - All handled by the Vue.js frontend
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/shop', [HomeController::class, 'index'])->name('shop');
    Route::get('/collection', [HomeController::class, 'index'])->name('collection');
    Route::get('/quick-wins', [HomeController::class, 'index'])->name('quick-wins');
    Route::get('/profile', [HomeController::class, 'index'])->name('profile');
    Route::get('/settings', [HomeController::class, 'index'])->name('settings');
});