<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateRolesTable
 * 
 * Migrasi tabel roles untuk sistem otorisasi multi-role (Admin & Customer).
 */
class CreateRolesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS roles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_roles_name ON roles (name);", $pdo);

        // Seed roles default secara aman untuk seluruh dialek (MySQL, MariaDB, SQLite, PostgreSQL)
        try {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
            $stmt->execute([1]);
            if (!$stmt->fetch()) {
                $ins = $pdo->prepare("INSERT INTO roles (id, name, display_name, description) VALUES (?, ?, ?, ?)");
                $ins->execute([1, 'admin', 'Administrator', 'Pengelola sistem, jadwal tayang, studio, dan transaksi']);
                $ins->execute([2, 'customer', 'Customer', 'Pengguna umum bioskop yang dapat memesan tiket']);
            }
        } catch (\Throwable $e) {
            // Abaikan jika sudah ada
        }
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS roles;", $pdo);
    }
}
