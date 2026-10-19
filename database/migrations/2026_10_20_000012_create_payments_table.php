<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreatePaymentsTable
 * 
 * Migrasi tabel transaksional payments (rekam status pembayaran Snap & Midtrans).
 */
class CreatePaymentsTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS payments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL UNIQUE,
                snap_token VARCHAR(255) NULL,
                payment_type VARCHAR(50) NULL,
                transaction_status VARCHAR(50) NOT NULL DEFAULT 'pending',
                fraud_status VARCHAR(50) NULL,
                midtrans_transaction_id VARCHAR(100) NULL,
                gross_amount DECIMAL(12, 2) NOT NULL,
                settlement_time DATETIME NULL,
                raw_response_json TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            );
        ", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payments_midtrans_tx ON payments (midtrans_transaction_id);", $pdo);
        $this->execute("CREATE INDEX IF NOT EXISTS idx_payments_status ON payments (transaction_status);", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS payments;", $pdo);
    }
}
