<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Authentication\AuthenticatedSessionController;
use App\Http\Controllers\Authentication\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HabitController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\MobileApiController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/mobile')->group(function () {
    Route::post('/login', [MobileApiController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth')->group(function () {
        Route::get('/me', [MobileApiController::class, 'me']);
        Route::post('/logout', [MobileApiController::class, 'logout']);
        Route::get('/dashboard', [MobileApiController::class, 'dashboard']);
        Route::get('/habits', [MobileApiController::class, 'habits']);
        Route::post('/habits', [MobileApiController::class, 'storeHabit']);
        Route::post('/habits/{habit}/complete', [MobileApiController::class, 'completeHabit']);
        Route::get('/journals', [MobileApiController::class, 'journals']);
        Route::post('/journals', [MobileApiController::class, 'storeJournal']);
    });
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/analytics', AnalyticsController::class)->name('analytics');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::controller(HabitController::class)->prefix('habits')->name('habits.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{habit}', 'update')->name('update');
        Route::patch('/{habit}/toggle', 'toggle')->name('toggle');
        Route::delete('/{habit}', 'destroy')->name('destroy');
    });

    Route::controller(JournalController::class)->prefix('journals')->name('journals.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::put('/{journal}', 'update')->name('update');
        Route::patch('/{journal}/{flag}', 'toggle')->whereIn('flag', ['favorite', 'bookmark'])->name('toggle');
        Route::delete('/{journal}', 'destroy')->name('destroy');
    });

    Route::get('/memories/drafts', [JournalController::class, 'drafts'])->name('memories.drafts');
    Route::get('/memories/timeline', [MemoryController::class, 'timeline'])->name('memories.timeline');
    Route::get('/memories/keepsakes', [MemoryController::class, 'keepsakes'])->name('memories.keepsakes');
    Route::get('/memories/photobooth', [MemoryController::class, 'photobooth'])->name('memories.photobooth');
    Route::post('/memories/photobooth/photos', [MemoryController::class, 'store'])->name('memories.photos.store');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->middleware('throttle:6,1')->name('settings.password.update');
    Route::patch('/settings/users/{member}/role', [SettingsController::class, 'updateRole'])->name('settings.users.role');
});
