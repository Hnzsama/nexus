<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class CreatePricingTiersTable
 * 
 * Migrasi tabel master pricing_tiers (struktur tarif harga tiket dinamis).
 */
class CreatePricingTiersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            CREATE TABLE IF NOT EXISTS pricing_tiers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                multiplier DECIMAL(5, 2) NOT NULL DEFAULT 1.00,
                surcharge DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
                description TEXT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        ", $pdo);
    }

    public function down(PDO $pdo): void
    {
        $this->execute("DROP TABLE IF EXISTS pricing_tiers;", $pdo);
    }
}
