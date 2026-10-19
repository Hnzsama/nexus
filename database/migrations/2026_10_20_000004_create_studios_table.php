<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateStudiosTable
 * 
 * Migrasi tabel master studios (data ruangan studio bioskop).
 */
class CreateStudiosTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS studios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                cinema_id INT NOT NULL,
                name VARCHAR(100) NOT NULL,
                studio_type VARCHAR(50) NOT NULL DEFAULT 'REGULAR',
                capacity INT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_studios_cinema FOREIGN KEY (cinema_id) REFERENCES cinemas (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_studios_cinema ON studios (cinema_id);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS studios;", $pdo);
    }
}
