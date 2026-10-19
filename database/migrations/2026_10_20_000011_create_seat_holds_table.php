<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateSeatHoldsTable
 * 
 * Migrasi tabel transaksional seat_holds (kritis untuk proteksi race condition & dual-layer expiry).
 */
class CreateSeatHoldsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS seat_holds (
                id INT AUTO_INCREMENT PRIMARY KEY,
                showtime_id INT NOT NULL,
                seat_id INT NOT NULL,
                order_id INT NOT NULL,
                user_id INT NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'HELD',
                held_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME NOT NULL,
                CONSTRAINT fk_holds_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_holds_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_seat_holds_composite ON seat_holds (showtime_id, seat_id, status);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_active_holds ON seat_holds (showtime_id, seat_id, status, expires_at);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_holds ON seat_holds (order_id);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS seat_holds;", $pdo);
    }
}
