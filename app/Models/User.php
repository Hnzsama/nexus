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
        'avatar',
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
}
