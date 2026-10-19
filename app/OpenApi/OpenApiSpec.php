<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'Dokumentasi interaktif OpenAPI 3.0 / Swagger UI untuk RESTful API Nexus MVC Framework.',
    title: 'Nexus Framework API'
)]
#[OA\Server(
    url: '/',
    description: 'Local Environment (Current Host)'
)]
#[OA\Server(
    url: 'https://persons-copies-intelligent-freelance.trycloudflare.com',
    description: 'Cloudflare Tunnel (Testing Server)'
)]
#[OA\Tag(
    name: 'Authentication & Accounts',
    description: 'Sistem autentikasi: Register, Login, Logout, Forgot Password, Reset Password, & Email Verification'
)]
#[OA\Tag(
    name: 'User Profile & Settings',
    description: 'Pengelolaan profil pengguna, upload avatar, dan ganti password akun'
)]
#[OA\Tag(
    name: 'Transactions & Checkout',
    description: 'Pessimistic locking, Canonical sorting anti-deadlock, dan Midtrans Snap token'
)]
#[OA\Tag(
    name: 'Seats & Real-Time Engine',
    description: 'Denah kursi real-time, status dinamis (Available, Held, Booked), dan Lazy Expiry'
)]
#[OA\Tag(
    name: 'Cinema & Movies',
    description: 'Katalog film, sinopsis, poster, dan slot jadwal tayang bioskop'
)]
#[OA\Tag(
    name: 'Payment & Webhook Engine',
    description: 'Webhook Midtrans idempoten, verifikasi kriptografi SHA-512, dan penerbitan tiket'
)]
#[OA\Tag(
    name: 'General',
    description: 'System health, heartbeat & framework metadata endpoints'
)]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    name: 'Authorization',
    in: 'header',
    bearerFormat: 'API Token',
    scheme: 'bearer',
    description: 'Masukkan API Token yang didapatkan setelah login (contoh: Bearer e2b10a9f...)'
)]
class OpenApiSpec
{
}
