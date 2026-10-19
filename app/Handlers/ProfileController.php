<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\Order;
use App\Models\User;
use App\Requests\ChangePasswordRequest;
use App\Requests\UpdateProfileRequest;
use Core\Http\Request;
use Core\Http\Response;
use Core\Storage\Storage;
use OpenApi\Attributes as OA;

/**
 * Class ProfileController
 * 
 * Pengendali profil pengguna, pembaruan avatar via Storage facade,
 * serta fitur ganti password yang aman.
 */
class ProfileController extends Controller
{
    #[OA\Get(
        path: '/api/user/profile',
        summary: 'Ambil Data Profil Pengguna',
        description: 'Mengambil data profil lengkap pengguna yang sedang login (nama, email, no telepon, avatar, status verifikasi) serta ringkasan total tiket bioskop yang pernah dipesan.',
        tags: ['User Profile & Settings'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil berhasil diambil.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'user', type: 'object'),
                        new OA\Property(property: 'total_orders', type: 'integer', example: 4),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Pengguna belum terautentikasi (Unauthenticated).')
        ]
    )]
    public function getProfile(Request $request): Response
    {
        $userObj = $request->user();
        if (!$userObj || !isset($userObj->id)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $user = User::find($userObj->id);
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Data pengguna tidak ditemukan.',
            ], 404);
        }

        $totalOrders = Order::where('user_id', (int) $user->id)->count();

        return Response::json([
            'status'       => 'success',
            'user'         => $user->toSafeArray(),
            'total_orders' => $totalOrders,
        ]);
    }

    #[OA\Post(
        path: '/api/user/profile',
        summary: 'Perbarui Data Profil & Avatar Pengguna',
        description: 'Memperbarui nama lengkap, nomor telepon, dan file avatar foto profil yang disimpan ke storage public.',
        tags: ['User Profile & Settings'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'Ahmad Fauzi'),
                        new OA\Property(property: 'phone', type: 'string', example: '081298765432'),
                        new OA\Property(property: 'avatar', type: 'string', format: 'binary', description: 'File gambar JPG/PNG profil'),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profil berhasil diperbarui.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Profil berhasil diperbarui.'),
                        new OA\Property(property: 'user', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 422, description: 'Validasi form profil gagal.')
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): Response
    {
        $userObj = $request->user();
        if (!$userObj || !isset($userObj->id)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $user = User::find($userObj->id);
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $user->name = trim((string) $request->input('name', $user->name));
        $user->phone = trim((string) $request->input('phone', $user->phone ?? ''));

        // Handle upload avatar jika ada berkas yang dikirim
        $avatarFile = $request->file('avatar');
        if ($avatarFile && $avatarFile->isValid()) {
            // Hapus avatar lama dari storage jika bukan default
            if (!empty($user->avatar)) {
                Storage::disk('public')->delete((string) $user->avatar);
            }

            // Simpan ke storage/app/public/avatars via Facade Storage
            $storedPath = $avatarFile->store('avatars', 'public');
            if ($storedPath !== false) {
                $user->avatar = $storedPath;
            }
        }

        $user->save();

        // Sinkronkan sesi login
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['user'] = $user->toSafeArray();

        return Response::json([
            'status'  => 'success',
            'message' => 'Profil dan data diri Anda berhasil diperbarui.',
            'user'    => $user->toSafeArray(),
        ]);
    }

    #[OA\Post(
        path: '/api/user/change-password',
        summary: 'Ganti Password Akun (Edit Password)',
        description: 'Mengubah password pengguna setelah memverifikasi kecocokan password lama (current_password).',
        tags: ['User Profile & Settings'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Masukkan password saat ini dan password baru:',
            content: [
                new OA\MediaType(
                    mediaType: 'application/x-www-form-urlencoded',
                    schema: new OA\Schema(
                        required: ['current_password', 'new_password'],
                        properties: [
                            new OA\Property(property: 'current_password', type: 'string', format: 'password', description: 'Kata Sandi Saat Ini'),
                            new OA\Property(property: 'new_password', type: 'string', format: 'password', description: 'Kata Sandi Baru (min. 8 karakter)'),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['current_password', 'new_password'],
                    properties: [
                        new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'RahasiaLama123!'),
                        new OA\Property(property: 'new_password', type: 'string', format: 'password', example: 'RahasiaBaru2026!'),
                    ]
                )
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password berhasil diubah.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'message', type: 'string', example: 'Password berhasil diperbarui.'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Password saat ini salah.'),
            new OA\Response(response: 401, description: 'Unauthenticated.'),
            new OA\Response(response: 422, description: 'Format password baru tidak valid.')
        ]
    )]
    public function changePassword(ChangePasswordRequest $request): Response
    {
        $userObj = $request->user();
        if (!$userObj || !isset($userObj->id)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Silakan login terlebih dahulu.',
            ], 401);
        }

        $user = User::find($userObj->id);
        if (!$user) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Pengguna tidak ditemukan.',
            ], 404);
        }

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');

        if (!password_verify($currentPassword, (string) $user->password)) {
            return Response::json([
                'status'  => 'error',
                'message' => 'Password saat ini (current password) tidak sesuai.',
            ], 400);
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->save();

        return Response::json([
            'status'  => 'success',
            'message' => 'Password akun Anda berhasil diperbarui dengan aman.',
        ]);
    }
}
