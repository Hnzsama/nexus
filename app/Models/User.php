<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class User
 * 
 * Model pengguna aplikasi berbasis Nexus Model.
 * 
 * @property int|string $id
 * @property int|string|null $role_id
 * @property string $role
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Model
{
    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'phone',
        'avatar',
        'email_verified_at',
        'verification_token',
        'reset_password_token',
        'reset_password_expires_at',
        'api_token',
    ];

    /**
     * Dapatkan URL publik avatar pengguna dari storage disk public.
     */
    public function avatarUrl(): ?string
    {
        if (!empty($this->avatar)) {
            return \Core\Storage\Storage::disk('public')->url($this->avatar);
        }
        return null;
    }

    /**
     * Cek apakah email user sudah diverifikasi.
     */
    public function isEmailVerified(): bool
    {
        return !empty($this->email_verified_at);
    }

    /**
     * Tandai email sebagai terverifikasi.
     */
    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = date('Y-m-d H:i:s');
        $this->verification_token = null;
        return $this->save();
    }

    /**
     * Generate API Bearer token baru untuk user.
     */
    public function generateApiToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->api_token = $token;
        $this->save();
        return $token;
    }

    /**
     * Dapatkan instance Model Role terkait.
     */
    public function roleModel(): ?Role
    {
        if (!empty($this->role_id)) {
            return Role::find($this->role_id);
        }
        if (!empty($this->role)) {
            return Role::where('name', $this->role)->first();
        }
        return null;
    }

    /**
     * Cek apakah pengguna memiliki hak akses Administrator.
     */
    public function isAdmin(): bool
    {
        return strtolower((string) ($this->role ?? '')) === 'admin'
            || (int) ($this->role_id ?? 0) === 1;
    }

    /**
     * Cek apakah pengguna merupakan Customer reguler.
     */
    public function isCustomer(): bool
    {
        return !$this->isAdmin();
    }

    /**
     * Kembalikan representasi array yang aman tanpa mengekspos hash password atau token rahasia.
     *
     * @return array<string, mixed>
     */
    public function toSafeArray(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'email' => (string) $this->email,
            'role' => (string) ($this->role ?? 'customer'),
            'role_id' => !empty($this->role_id) ? (int) $this->role_id : null,
            'phone' => (string) ($this->phone ?? ''),
            'avatar' => (string) ($this->avatar ?? ''),
            'avatar_url' => $this->avatarUrl(),
            'is_verified' => $this->isEmailVerified(),
            'email_verified_at' => $this->email_verified_at,
            'created_at' => (string) ($this->created_at ?? ''),
        ];
    }
}
