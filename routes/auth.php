<?php

declare(strict_types=1);

use App\Handlers\AuthController;
use App\Handlers\ProfileController;
use App\Middlewares\AuthMiddleware;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Authentication & User Profile Routes
|--------------------------------------------------------------------------
|
| Seluruh rute otentikasi (Register, Login, Logout, Forgot Password,
| Reset Password, Email Verification) serta manajemen profil & pengaturan
| akun pengguna didaftarkan di sini.
|
*/

// =========================================================================
// 1. PUBLIC AUTHENTICATION API & FORM ENDPOINTS
// =========================================================================

// JSON API Endpoints
Router::post('/api/auth/register', [AuthController::class, 'register'])->name('api.auth.register');
Router::post('/api/auth/login', [AuthController::class, 'login'])->name('api.auth.login');
Router::post('/api/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
Router::post('/api/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
Router::post('/api/auth/reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');
Router::post('/api/auth/verify-email', [AuthController::class, 'verifyEmail'])->name('api.auth.verify-email');
Router::get('/api/auth/verify-email', [AuthController::class, 'verifyEmail'])->name('api.auth.verify-email.get');
Router::post('/api/auth/resend-verification', [AuthController::class, 'resendVerification'])->name('api.auth.resend-verification');

// Web Form Action Aliases
Router::post('/register', [AuthController::class, 'register'])->name('auth.register');
Router::post('/login', [AuthController::class, 'login'])->name('auth.login');
Router::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
Router::post('/forgot-password', [AuthController::class, 'forgotPassword'])->name('auth.forgot-password');
Router::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

// =========================================================================
// 2. PROTECTED USER PROFILE & SETTINGS ENDPOINTS
// =========================================================================

Router::get('/api/user/profile', [ProfileController::class, 'getProfile'])
    ->middleware(AuthMiddleware::class)
    ->name('api.user.profile');

Router::post('/api/user/profile', [ProfileController::class, 'updateProfile'])
    ->middleware(AuthMiddleware::class)
    ->name('api.user.profile.update');

Router::post('/api/user/change-password', [ProfileController::class, 'changePassword'])
    ->middleware(AuthMiddleware::class)
    ->name('api.user.password.change');
