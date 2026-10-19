<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreateTicketsTable
 * 
 * Migrasi tabel transaksional tickets (e-ticket digital dengan QR token unik untuk scan di gate bioskop).
 */
class CreateTicketsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS tickets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_item_id INT NOT NULL UNIQUE,
                ticket_code VARCHAR(50) NOT NULL UNIQUE,
                qr_token VARCHAR(255) NOT NULL UNIQUE,
                checkin_status VARCHAR(50) NOT NULL DEFAULT 'UNCLAIMED',
                checked_in_at DATETIME NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_tickets_order_item FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE RESTRICT
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_tickets_code ON tickets (ticket_code);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_tickets_qr ON tickets (qr_token);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS tickets;", $pdo);
    }
}
