<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Role
 * 
 * Model peran (role) pengguna dalam sistem (misal: 'admin', 'customer').
 * 
 * @property int|string $id
 * @property string $name
 * @property string $display_name
 * @property string|null $description
 * @property string $created_at
 * @property string $updated_at
 */
class Role extends Model
{
    protected string $table = 'roles';

    protected array $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public const ADMIN = 'admin';
    public const CUSTOMER = 'customer';

    /**
     * Dapatkan semua user yang memiliki role ini.
     *
     * @return User[]
     */
    public function users(): array
    {
        return User::where('role_id', $this->id)->get();
    }
}
