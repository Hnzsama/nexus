<?php

declare(strict_types=1);

namespace App\Handlers;

use App\Models\User;
use App\Requests\StoreUserRequest;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Class UserController
 * 
 * Controller untuk mengelola data pengguna (CRUD)
 * sekaligus sebagai demonstrasi integrasi Active Record ORM, FormRequest Validation,
 * flash session, dan View Engine Nexus.
 */
class UserController extends Controller
{
    /**
     * Tampilkan halaman daftar user, statistik ORM, dan formulir input dengan validator.
     */
    public function index(Request $request): Response
    {
        $users = User::latest('id')->get();
        $totalUsers = User::count();

        return $this->view('pages/users/index', [
            'users' => $users,
            'totalUsers' => $totalUsers,
            'success' => flash('success') ?? $request->query('success'),
            'error' => flash('error') ?? $request->query('error'),
        ]);
    }

    /**
     * Simpan user baru ke database menggunakan StoreUserRequest (Validator) & Active Record Model.
     */
    public function store(StoreUserRequest $request): Response
    {
        // Data di sini dijamin telah lolos validasi (rules, min, max, email, unique, dll.)
        $validated = $request->validated();

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarFile = $request->file('avatar');
            if ($avatarFile && $avatarFile->isValid()) {
                $avatarPath = $avatarFile->store('avatars', 'public');
            }
        }

        // Simpan menggunakan ORM Model::create()
        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
            'avatar'   => $avatarPath,
        ]);

        if (!$request->header('x-nexus-spa') && $request->wantsJson()) {
            return $this->json([
                'status'  => 'success',
                'message' => "User {$validated['name']} berhasil ditambahkan!",
            ]);
        }

        $flashMsg = $avatarPath 
            ? "User '{$validated['name']}' dan foto profil berhasil diunggah ke Storage!"
            : "User '{$validated['name']}' berhasil dibuat & divalidasi oleh Request Validator!";

        set_flash('success', $flashMsg);
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        return $this->redirect(route('users.index'));
    }

    /**
     * Hapus user berdasar ID menggunakan Active Record $user->delete().
     */
    public function destroy(Request $request, string $id): Response
    {
        $user = User::find($id);

        if ($user) {
            $name = $user->name;
            
            // Hapus file avatar dari Storage jika ada
            if (!empty($user->avatar)) {
                \Core\Storage\Storage::disk('public')->delete($user->avatar);
            }

            $user->delete();

            if (!$request->header('x-nexus-spa') && $request->wantsJson()) {
                return $this->json([
                    'status'  => 'success',
                    'message' => "User {$name} berhasil dihapus.",
                ]);
            }

            set_flash('success', "User '{$name}' berhasil dihapus dari database.");
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
            return $this->redirect(route('users.index'));
        }

        set_flash('error', 'User dengan ID tersebut tidak ditemukan.');
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        return $this->redirect(route('users.index'));
    }

    /**
     * Helper untuk membuat data dummy pengguna (seed).
     */
    public function seed(Request $request): Response
    {
        $samples = [
            ['name' => 'Akuma Architect', 'email' => 'architect@nexus.dev', 'password' => 'password123'],
            ['name' => 'Sarah Connor', 'email' => 'sarah@resistance.dev', 'password' => 'password123'],
            ['name' => 'Neo Anderson', 'email' => 'neo@matrix.dev', 'password' => 'password123'],
        ];

        $created = 0;
        foreach ($samples as $item) {
            if (!User::where('email', '=', $item['email'])->exists()) {
                User::create([
                    'name'     => $item['name'],
                    'email'    => $item['email'],
                    'password' => password_hash($item['password'], PASSWORD_BCRYPT),
                ]);
                $created++;
            }
        }

        set_flash('success', "{$created} data dummy user berhasil di-generate ke database!");
        return $this->redirect(route('users.index'));
    }
}