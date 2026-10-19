<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateMoviesTable
 * 
 * Migrasi tabel master movies (data katalog film).
 */
class CreateMoviesTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                synopsis TEXT NULL,
                duration_minutes INT NOT NULL,
                age_rating VARCHAR(10) NOT NULL DEFAULT '13+',
                poster_url VARCHAR(500) NULL,
                trailer_url VARCHAR(500) NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'NOW_PLAYING',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_movies_status ON movies (status);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS movies;", $pdo);
    }
}
