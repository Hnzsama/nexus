<?php

declare(strict_types=1);

use App\Handlers\Auth\ForgotPasswordController;
use App\Handlers\Auth\LoginController;
use App\Handlers\Auth\RegisterController;
use App\Handlers\Auth\ResetPasswordController;
use App\Handlers\Auth\VerificationController;
use App\Handlers\ProfileController;
use App\Middlewares\AuthMiddleware;
use Core\Http\Router;

/*
|--------------------------------------------------------------------------
| Modular Authentication & User Profile Routes
|--------------------------------------------------------------------------
|
| Rute otentikasi dipisahkan secara modular ke masing-masing controller:
| - RegisterController: Registrasi pengguna baru
| - LoginController: Login & Logout
| - ForgotPasswordController: Pengajuan lupa password
| - ResetPasswordController: Eksekusi reset password baru
| - VerificationController: Verifikasi email & kirim ulang kode
| - ProfileController: Lihat/edit profil & ubah password
|
*/

// =========================================================================
// 1. PUBLIC AUTHENTICATION API & FORM ENDPOINTS
// =========================================================================

// JSON API Endpoints
Router::post('/api/auth/register', [RegisterController::class, 'register'])->name('api.auth.register');
Router::post('/api/auth/login', [LoginController::class, 'login'])->name('api.auth.login');
Router::post('/api/auth/logout', [LoginController::class, 'logout'])->name('api.auth.logout');
Router::post('/api/auth/forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->name('api.auth.forgot-password');
Router::post('/api/auth/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('api.auth.reset-password');
Router::post('/api/auth/verify-email', [VerificationController::class, 'verifyEmail'])->name('api.auth.verify-email');
Router::get('/api/auth/verify-email', [VerificationController::class, 'verifyEmail'])->name('api.auth.verify-email.get');
Router::post('/api/auth/resend-verification', [VerificationController::class, 'resendVerification'])->name('api.auth.resend-verification');

// Web Form Action Aliases
Router::post('/register', [RegisterController::class, 'register'])->name('auth.register');
Router::post('/login', [LoginController::class, 'login'])->name('auth.login');
Router::post('/logout', [LoginController::class, 'logout'])->name('auth.logout');
Router::post('/forgot-password', [ForgotPasswordController::class, 'forgotPassword'])->name('auth.forgot-password');
Router::post('/reset-password', [ResetPasswordController::class, 'resetPassword'])->name('auth.reset-password');

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
