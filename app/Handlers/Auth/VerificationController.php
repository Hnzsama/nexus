<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Handlers\Controller;
use App\Models\User;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class VerificationController
 * 
 * Menangani verifikasi alamat email dan pengiriman ulang (resend) kode verifikasi.
 */
class VerificationController extends Controller
{
    #[OA\Post(
        path: '/api/auth/verify-email',
        summary: 'Verifikasi Alamat Email',
        description: 'Memvalidasi kode verifikasi email pengguna dan menandai akun terverifikasi (email_verified_at).',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan kode verifikasi email 6-digit:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['token'],
                        properties: [
                            new OA\Property(property: 'token', type: 'string', example: '', description: 'Kode Verifikasi 6-Digit (dari registrasi)'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email (opsional jika token unik)'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['token'],
                    properties: [
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'fauzi@example.com'),
                        new OA\Property(property: 'token', type: 'string', example: '982134'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email berhasil diverifikasi.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Email Anda telah berhasil diverifikasi.'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Kode token verifikasi tidak valid atau tidak cocok.'),
        ]
    )]
    public function verifyEmail(Request $request): Response
    {
        $token = trim((string) ($request->input('token') ?? $request->query('token', '')));
        $email = strtolower(trim((string) ($request->input('email') ?? $request->query('email', ''))));

        if (empty($token)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Token verifikasi email wajib disertakan.',
            ], 422);
        }

        $query = User::where('verification_token', $token);
        if (!empty($email)) {
            $query = $query->where('email', $email);
        }
        $user = $query->first();

        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Kode token verifikasi tidak valid atau sudah digunakan.',
            ], 400);
        }

        $user->markEmailAsVerified();

        return Response::json([
            'status'  => 'success',
            'message' => 'Selamat! Alamat email Anda telah berhasil diverifikasi.',
            'user'    => $user->toSafeArray(),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/resend-verification',
        summary: 'Kirim Ulang Kode Verifikasi Email',
        description: 'Menghasilkan kode verifikasi baru jika kode sebelumnya kedaluwarsa atau hilang.',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan email akun untuk pengiriman ulang kode verifikasi:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['email'],
                        properties: [
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email Akun'),
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
                description: 'Kode verifikasi baru berhasil dibuat.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Kode verifikasi baru berhasil dikirimkan.'),
                        new OA\Property(property: 'verification_token', type: 'string', example: '492810'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Email pengguna sudah terverifikasi sebelumnya.'),
            new OA\Response(response: 404, description: 'Email tidak ditemukan.'),
        ]
    )]
    public function resendVerification(Request $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        if (empty($email)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Alamat email wajib diisi.',
            ], 422);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Akun dengan email tersebut tidak ditemukan.',
            ], 404);
        }

        if ($user->isEmailVerified()) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Akun ini sudah terverifikasi sebelumnya.',
            ], 400);
        }

        $newVerif = (string) random_int(100000, 999999);
        $user->verification_token = $newVerif;
        $user->save();

        return Response::json([
            'status'             => 'success',
            'message'            => 'Kode verifikasi baru berhasil dikirimkan ke email Anda.',
            'verification_token' => $newVerif,
        ]);
    }
}
