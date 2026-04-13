<?php

use App\Http\Controllers\Admin\MatchController as AdminMatchController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MatchController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public
Route::get('/', fn() => Inertia::render('Home'))->name('home');
Route::get('/quiz', [QuizController::class, 'index'])->name('quiz.index');
Route::get('/quiz/{quiz}', [QuizController::class, 'show'])->name('quiz.show');
Route::get('/match', [MatchController::class, 'index'])->name('match.index');
Route::get('/match/{match}', [MatchController::class, 'show'])->name('match.show');

// Admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', fn() => Inertia::render('Admin/Dashboard'))->name('dashboard');

    // Quiz
    Route::get('/quiz', [AdminQuizController::class, 'index'])->name('quiz.index');
    Route::post('/quiz', [AdminQuizController::class, 'create'])->name('quiz.create');
    Route::post('/quiz/upload-image', [AdminQuizController::class, 'uploadImage'])->name('quiz.upload-image');
    Route::post('/quiz/copy-image', [AdminQuizController::class, 'copyImage'])->name('quiz.copy-image');
    Route::get('/quiz/{quiz}/edit', [AdminQuizController::class, 'edit'])->name('quiz.edit');
    Route::put('/quiz/{quiz}', [AdminQuizController::class, 'update'])->name('quiz.update');
    Route::delete('/quiz/{quiz}', [AdminQuizController::class, 'destroy'])->name('quiz.destroy');

    // Match
    Route::get('/match', [AdminMatchController::class, 'index'])->name('match.index');
    Route::post('/match', [AdminMatchController::class, 'create'])->name('match.create');
    Route::post('/match/upload-image', [AdminMatchController::class, 'uploadImage'])->name('match.upload-image');
    Route::get('/match/{match}/edit', [AdminMatchController::class, 'edit'])->name('match.edit');
    Route::put('/match/{match}', [AdminMatchController::class, 'update'])->name('match.update');
    Route::delete('/match/{match}', [AdminMatchController::class, 'destroy'])->name('match.destroy');

    // Settings
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');
    Route::post('/settings/prompts', [AdminSettingsController::class, 'updatePrompts'])->name('settings.prompts');
    Route::post('/settings/cleanup-images', [AdminSettingsController::class, 'cleanupImages'])->name('settings.cleanup-images');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
