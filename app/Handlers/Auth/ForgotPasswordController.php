<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Handlers\Controller;
use App\Models\User;
use App\Requests\ForgotPasswordRequest;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class ForgotPasswordController
 * 
 * Menangani permintaan pemulihan kata sandi (lupa password).
 */
class ForgotPasswordController extends Controller
{
    #[OA\Post(
        path: '/api/auth/forgot-password',
        summary: 'Permohonan Lupa Password (Forgot Password)',
        description: 'Membuat token reset password sementara (berlaku 15 menit) dan mengirimkan instruksi pemulihan ke email pengguna.',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan email akun yang ingin direset:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['email'],
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email Terdaftar'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['email'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'fauzi@example.com'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token instruksi reset password berhasil diterbitkan.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Instruksi reset password telah dikirim ke email Anda.'),
                        new OA\Property(property: 'reset_token', type: 'string', example: 'RESET-718293'),
                        new OA\Property(property: 'expires_at', type: 'string', example: '2026-10-20 01:30:00'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Alamat email tidak terdaftar di sistem.'),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Alamat email tidak ditemukan di sistem kami.',
            ], 404);
        }

        // Token 6 digit dengan masa berlaku 15 menit (900 detik)
        $resetToken = (string) random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + 900);

        $user->reset_password_token = $resetToken;
        $user->reset_password_expires_at = $expiresAt;
        $user->save();

        return Response::json([
            'status'      => 'success',
            'message'     => 'Instruksi reset password telah dikirimkan ke email Anda. Gunakan kode verifikasi di bawah ini.',
            'reset_token' => $resetToken,
            'expires_at'  => $expiresAt,
        ]);
    }
}
