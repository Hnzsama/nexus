<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Core\Database\Connection;
use PDO;

/**
 * Class RoleSeeder
 * 
 * Seeder untuk menginisialisasi roles ('admin', 'customer')
 * dan akun default Administrator.
 */
class RoleSeeder
{
    public static function run(?PDO $pdo = null): void
    {
        $pdo ??= Connection::getPdo();

        // 1. Pastikan roles tersedia
        $roles = [
            [
                'id'           => 1,
                'name'         => Role::ADMIN,
                'display_name' => 'Administrator',
                'description'  => 'Pengelola utama sistem bioskop, studio, film, jadwal, dan transaksi.',
            ],
            [
                'id'           => 2,
                'name'         => Role::CUSTOMER,
                'display_name' => 'Customer',
                'description'  => 'Pelanggan bioskop yang dapat memesan kursi dan melakukan pembayaran.',
            ],
        ];

        foreach ($roles as $r) {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ? OR name = ?");
            $stmt->execute([$r['id'], $r['name']]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("
                    INSERT INTO roles (id, name, display_name, description, created_at, updated_at)
                    VALUES (?, ?, ?, ?, datetime('now'), datetime('now'))
                ");
                $ins->execute([$r['id'], $r['name'], $r['display_name'], $r['description']]);
            }
        }

        // 2. Buat akun Default Administrator jika belum ada
        $adminEmail = 'admin@nexus.test';
        $existingAdmin = User::where('email', $adminEmail)->first();

        if (!$existingAdmin) {
            $admin = new User();
            $admin->name = 'Super Administrator';
            $admin->email = $adminEmail;
            $admin->password = password_hash('Admin123!', PASSWORD_BCRYPT);
            $admin->role = Role::ADMIN;
            $admin->role_id = 1;
            $admin->phone = '081122334455';
            $admin->email_verified_at = date('Y-m-d H:i:s');
            $admin->api_token = bin2hex(random_bytes(32));
            $admin->save();
        } else {
            // Pastikan role-nya admin
            $existingAdmin->role = Role::ADMIN;
            $existingAdmin->role_id = 1;
            $existingAdmin->save();
        }
    }
}
