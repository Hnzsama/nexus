<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Handlers\Controller;
use App\Models\User;
use App\Requests\ResetPasswordRequest;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class ResetPasswordController
 * 
 * Menangani eksekusi pembaruan kata sandi baru menggunakan token reset.
 */
class ResetPasswordController extends Controller
{
    #[OA\Post(
        path: '/api/auth/reset-password',
        summary: 'Eksekusi Reset Password Baru',
        description: 'Memvalidasi kode token reset password dan masa berlakunya, lalu mengganti password akun dengan hash baru.',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan email, kode token, dan password baru:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['email', 'token', 'password'],
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email Terdaftar'),
                            new OA\Property(property: 'token', type: 'string', example: '', description: 'Kode Token Reset 6-Digit'),
                            new OA\Property(property: 'password', type: 'string', format: 'password', example: '', description: 'Kata Sandi Baru (min. 8 karakter)'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['email', 'token', 'password'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'fauzi@example.com'),
                        new OA\Property(property: 'token', type: 'string', example: '718293'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'PasswordBaru2026!'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password berhasil direset.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Password berhasil diperbarui. Silakan login kembali.'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Token salah atau telah kedaluwarsa.'),
            new OA\Response(response: 404, description: 'Pengguna tidak ditemukan.'),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $token = trim((string) $request->input('token'));
        $newPassword = (string) $request->input('password');

        $user = User::where('email', $email)->first();
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Akun dengan email tersebut tidak ditemukan.',
            ], 404);
        }

        // Cek kecocokan token
        if (empty($user->reset_password_token) || $user->reset_password_token !== $token) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Kode token reset password tidak valid.',
            ], 400);
        }

        // Cek waktu kedaluwarsa
        if (!empty($user->reset_password_expires_at) && strtotime((string)$user->reset_password_expires_at) < time()) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Kode token reset password telah kedaluwarsa. Silakan ajukan lupa password kembali.',
            ], 400);
        }

        // Update password dan bersihkan token
        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->reset_password_token = null;
        $user->reset_password_expires_at = null;
        $user->api_token = null; // Logout dari semua sesi aktif
        $user->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Password berhasil diperbarui! Silakan login dengan password baru Anda.',
        ]);
    }
}
