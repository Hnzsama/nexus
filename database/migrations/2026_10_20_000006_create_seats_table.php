<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateSeatsTable
 * 
 * Migrasi tabel master seats (data denah kursi studio bioskop).
 */
class CreateSeatsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS seats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                studio_id INT NOT NULL,
                row_label VARCHAR(5) NOT NULL,
                seat_number INT NOT NULL,
                seat_type VARCHAR(50) NOT NULL DEFAULT 'STANDARD',
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uk_studio_seat UNIQUE (studio_id, row_label, seat_number),
                CONSTRAINT fk_seats_studio FOREIGN KEY (studio_id) REFERENCES studios (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_seats_studio ON seats (studio_id, is_active);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS seats;", $pdo);
    }
}
