<?php

declare(strict_types=1);

namespace App\Handlers\Auth;

use App\Handlers\Controller;
use App\Models\User;
use App\Requests\RegisterRequest;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class RegisterController
 * 
 * Menangani proses pendaftaran akun pengguna baru.
 */
class RegisterController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Registrasi Pengguna Baru',
        description: 'Mendaftarkan akun baru ke sistem bioskop dengan password terenkripsi bcrypt, membuat token verifikasi email, dan mengembalikan data pengguna serta API Bearer Token.',
        tags: ['Authentication & Accounts'],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Isi formulir pendaftaran di bawah ini:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['name', 'email', 'password'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', example: '', description: 'Nama Lengkap'),
                            new OA\Property(property: 'email', type: 'string', format: 'email', example: '', description: 'Alamat Email Aktif'),
                            new OA\Property(property: 'password', type: 'string', format: 'password', example: '', description: 'Kata Sandi (min. 8 karakter)'),
                            new OA\Property(property: 'phone', type: 'string', example: '', description: 'Nomor WhatsApp / HP'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['name', 'email', 'password'],
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Ahmad Fauzi'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'fauzi@example.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Rahasia123!'),
                        new OA\Property(property: 'phone', type: 'string', example: '081234567890'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Akun berhasil didaftarkan.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Registrasi berhasil! Silakan verifikasi email Anda.'),
                        new OA\Property(property: 'token', type: 'string', example: 'e2b10a9f...'),
                        new OA\Property(property: 'verification_token', type: 'string', example: 'VERIF-982134'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 409, description: 'Email sudah terdaftar sebelumnya.'),
            new OA\Response(response: 422, description: 'Validasi formulir registrasi gagal.')
        ]
    )]
    public function register(RegisterRequest $request): Response
    {
        $email = strtolower(trim((string) $request->input('email')));
        $name = trim((string) $request->input('name'));
        $password = (string) $request->input('password');
        $phone = trim((string) $request->input('phone', ''));

        // Cek duplikasi email
        $existing = User::where('email', $email)->first();
        if ($existing) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Alamat email sudah terdaftar. Silakan gunakan email lain atau login.',
            ], 409);
        }

        // Generate token verifikasi email (6 digit)
        $verifToken = (string) random_int(100000, 999999);

        // Buat user baru
        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->phone = $phone;
        $user->verification_token = $verifToken;
        $user->api_token = bin2hex(random_bytes(32));
        $user->save();

        // Mulai sesi login
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user'] = $user->toSafeArray();

        return Response::json([
            'status'             => 'success',
            'message'            => 'Registrasi berhasil! Kode verifikasi telah dikirimkan ke email Anda.',
            'token'              => $user->api_token,
            'verification_token' => $verifToken,
            'user'               => $user->toSafeArray(),
        ], 201);
    }
}
