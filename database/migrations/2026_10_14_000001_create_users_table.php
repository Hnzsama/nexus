<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateUsersTable
 * 
 * Migrasi tabel users menggunakan query SQL murni.
 * Cukup tulis SQL standar sekali saja via $this->execute(),
 * framework otomatis menyesuaikan dialek (SQLite, MySQL, PostgreSQL) di belakang layar.
 */
class CreateUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL
            );
        ", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS users;", $pdo);
    }
}
