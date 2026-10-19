<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateOrdersTable
 * 
 * Migrasi tabel transaksional orders (pesanan tiket bioskop).
 */
class CreateOrdersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(64) NOT NULL UNIQUE,
                user_id INT NOT NULL,
                showtime_id INT NOT NULL,
                total_seats INT NOT NULL,
                total_amount DECIMAL(12, 2) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT 'UNPAID',
                expires_at DATETIME NOT NULL,
                paid_at DATETIME NULL,
                cancelled_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT,
                CONSTRAINT fk_orders_showtime FOREIGN KEY (showtime_id) REFERENCES showtimes (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_user ON orders (user_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_showtime ON orders (showtime_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_orders_status_expires ON orders (status, expires_at);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS orders;", $pdo);
    }
}
