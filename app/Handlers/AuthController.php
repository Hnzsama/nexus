<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\User;
use App\Requests\ForgotPasswordRequest;
use App\Requests\LoginRequest;
use App\Requests\RegisterRequest;
use App\Requests\ResetPasswordRequest;
use App\Requests\VerifyEmailRequest;
use Core\Http\Request;
use Core\Http\Response;
use OpenApi\Attributes as OA;

/**
 * Class AuthController
 * 
 * Pengendali utama seluruh siklus autentikasi pengguna:
 * Register, Login, Logout, Forgot Password, Reset Password, & Email Verification.
 */
class AuthController extends Controller
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
