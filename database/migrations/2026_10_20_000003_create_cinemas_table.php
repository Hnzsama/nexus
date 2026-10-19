<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateCinemasTable
 * 
 * Migrasi tabel master cinemas (data lokasi bioskop).
 */
class CreateCinemasTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS cinemas (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(255) NOT NULL,
                city VARCHAR(100) NOT NULL,
                address TEXT NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_cinemas_city ON cinemas (city);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS cinemas;", $pdo);
    }
}
