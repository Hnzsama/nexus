<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateOrderItemsTable
 * 
 * Migrasi tabel transaksional order_items (detail item kursi per pesanan).
 */
class CreateOrderItemsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                seat_id INT NOT NULL,
                price DECIMAL(12, 2) NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uk_order_seat UNIQUE (order_id, seat_id),
                CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_order_items_seat FOREIGN KEY (seat_id) REFERENCES seats (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_order_seat ON order_items (order_id, seat_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_order_items_seat ON order_items (seat_id);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS order_items;", $pdo);
    }
}
