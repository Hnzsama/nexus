<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class AddRoleToUsersTable
 * 
 * Menambahkan kolom role dan role_id pada tabel users.
 */
class AddRoleToUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $columns = [
            "role VARCHAR(50) NOT NULL DEFAULT 'customer'",
            "role_id INT NULL DEFAULT 2",
        ];

        foreach ($columns as $column) {
            try {
                $this->execute("ALTER TABLE users ADD COLUMN {$column};", $pdo);
            } catch (\Throwable $e) {
                // Abaikan jika kolom sudah ada
            }
        }

        try {
            $this->execute("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);", $pdo);
            $this->execute("CREATE INDEX IF NOT EXISTS idx_users_role_id ON users (role_id);", $pdo);
        } catch (\Throwable $e) {
            // Abaikan jika indeks sudah ada
        }
    }

    public function down(PDO $pdo): void
    {
        // Rollback opsional
    }
}
