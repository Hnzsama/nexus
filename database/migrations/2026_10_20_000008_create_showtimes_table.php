<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateShowtimesTable
 * 
 * Migrasi tabel master showtimes (slot jadwal penayangan film di studio).
 */
class CreateShowtimesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS showtimes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                movie_id INT NOT NULL,
                studio_id INT NOT NULL,
                start_time DATETIME NOT NULL,
                end_time DATETIME NOT NULL,
                base_price DECIMAL(12, 2) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'ACTIVE',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_showtimes_movie FOREIGN KEY (movie_id) REFERENCES movies (id) ON DELETE RESTRICT,
                CONSTRAINT fk_showtimes_studio FOREIGN KEY (studio_id) REFERENCES studios (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_showtimes_movie_studio_time ON showtimes (movie_id, studio_id, start_time);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_showtimes_schedule ON showtimes (studio_id, start_time);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS showtimes;", $pdo);
    }
}
