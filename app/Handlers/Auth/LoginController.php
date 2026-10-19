<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Handlers\Controller;
use App\Models\User;
use App\Requests\LoginRequest;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class LoginController
 * 
 * Menangani proses otentikasi login dan pengakhiran sesi (logout) pengguna.
 */
class LoginController extends Controller
{
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login Pengguna & Mendapatkan API Token',
        description: 'Memverifikasi kredensial email dan password pengguna, lalu menerbitkan Bearer Token dan sesi login aktif.',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan email dan password untuk login:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['email', 'password'],
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email Terdaftar'),
                            new OA\Property(property: 'password', type: 'string', format: 'password', example: '', description: 'Kata Sandi Akun'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['email', 'password'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'fauzi@example.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Rahasia123!'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login berhasil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Login berhasil.'),
                        new OA\Property(property: 'token', type: 'string', example: 'e2b10a9f...'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Kredensial tidak valid (email atau password salah).'),
            new OA\Response(response: 422, description: 'Parameter email atau password tidak lengkap.')
        ]
    )]
    public function login(LoginRequest $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $password = (string) $request->input('password');

        $user = User::where('email', $email)->first();

        if (!$user || !password_verify($password, (string) $user->password)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Email atau password yang Anda masukkan salah.',
            ], 401);
        }

        // Regenerate API token baru setiap login
        $token = $user->generateApiToken();

        // Simpan sesi web
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user'] = $user->toSafeArray();

        return Response::json([
            'status'  => 'success',
            'message' => 'Login berhasil. Selamat datang kembali, ' . $user->name . '!',
            'token'   => $token,
            'user'    => $user->toSafeArray(),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout Pengguna',
        description: 'Menghapus sesi login aktif di server dan mengosongkan API Token pengguna.',
        tags: ['Authentication & Accounts'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout berhasil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Anda telah berhasil logout.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Pengguna belum terautentikasi (Unauthenticated).')
        ]
    )]
    public function logout(Request $request): Response
    {
        $user = $request->user();
        if ($user && isset($user->id)) {
            $dbUser = User::find($user->id);
            if ($dbUser) {
                $dbUser->api_token = null;
                $dbUser->save();
            }
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        unset($_SESSION['user_id'], $_SESSION['user']);
        session_destroy();

        return Response::json([
            'status'  => 'success',
            'message' => 'Anda telah berhasil logout.',
        ]);
    }
}
