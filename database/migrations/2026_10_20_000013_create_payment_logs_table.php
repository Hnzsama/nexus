<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreatePaymentLogsTable
 * 
 * Migrasi tabel transaksional payment_logs (audit trail seluruh incoming webhook kriptografis).
 */
class CreatePaymentLogsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS payment_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(100) NOT NULL,
                event_type VARCHAR(50) NOT NULL DEFAULT 'webhook',
                status_code VARCHAR(20) NULL,
                raw_payload TEXT NOT NULL,
                ip_address VARCHAR(50) NULL,
                signature_valid TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payment_logs_order ON payment_logs (order_number);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS payment_logs;", $pdo);
    }
}
