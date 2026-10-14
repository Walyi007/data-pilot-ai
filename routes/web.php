<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatasetImportController;
use App\Http\Controllers\DatasetCleaningController;
use App\Http\Controllers\DatasetAnalyticsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\ReportController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dataset.import');
    }
    return redirect()->route('login');
});

// Dataset import routes
Route::get('/dataset/import', [DatasetImportController::class, 'create'])->name('dataset.import');
Route::post('/dataset', [DatasetImportController::class, 'store'])->name('dataset.store');
Route::get('/dataset/{dataset}', [DatasetImportController::class, 'show'])->name('dataset.show');

// Dataset cleaning routes
Route::get('/dataset/{dataset}/clean', [DatasetCleaningController::class, 'index'])->name('dataset.clean');
Route::post('/dataset/{dataset}/clean', [DatasetCleaningController::class, 'apply'])->name('dataset.clean.apply');

// Dataset analytics routes
Route::get('/dataset/{dataset}/analytics', [DatasetAnalyticsController::class, 'show'])->name('dataset.analytics');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Password reset routes
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
    ->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
    ->name('password.email');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])
    ->name('password.update');

// Profile routes
Route::get('/profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('profile.show');
Route::post('/profile', [ProfileController::class, 'update'])
    ->middleware('auth')
    ->name('profile.update');

// Report generation routes
Route::get('/dataset/{dataset}/report/profile/pdf', [ReportController::class, 'profilePdf'])
    ->middleware('auth')
    ->name('dataset.report.profile.pdf');
Route::get('/dataset/{dataset}/report/profile/excel', [ReportController::class, 'profileExcel'])
    ->middleware('auth')
    ->name('dataset.report.profile.excel');
Route::get('/dataset/{dataset}/report/analytics/pdf', [ReportController::class, 'analyticsPdf'])
    ->middleware('auth')
    ->name('dataset.report.analytics.pdf');
Route::get('/dataset/{dataset}/report/analytics/excel', [ReportController::class, 'analyticsExcel'])
    ->middleware('auth')
    ->name('dataset.report.analytics.excel');
Route::get('/dataset/{dataset}/report/cleaning/pdf', [ReportController::class, 'cleaningPdf'])
    ->middleware('auth')
    ->name('dataset.report.cleaning.pdf');