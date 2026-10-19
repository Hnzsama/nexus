<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class AddAuthFieldsToUsersTable
 * 
 * Menambahkan kolom-kolom untuk manajemen autentikasi lengkap:
 * - phone (jika belum ada)
 * - email_verified_at & verification_token (Email Verification)
 * - reset_password_token & reset_password_expires_at (Forgot & Reset Password)
 * - api_token (Bearer Token Authentication)
 */
class AddAuthFieldsToUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $columns = [
            "phone VARCHAR(30) NULL DEFAULT ''",
            "email_verified_at DATETIME NULL",
            "verification_token VARCHAR(100) NULL",
            "reset_password_token VARCHAR(100) NULL",
            "reset_password_expires_at DATETIME NULL",
            "api_token VARCHAR(100) NULL",
        ];

        foreach ($columns as $column) {
            try {
                $this->execute("ALTER TABLE users ADD COLUMN {$column};", $pdo);
            } catch (\Throwable $e) {
                // Kolom mungkin sudah ada sebelumnya pada dialek database tertentu
            }
        }

        try {
            $this->execute("CREATE INDEX IF NOT EXISTS idx_users_api_token ON users (api_token);", $pdo);
            $this->execute("CREATE INDEX IF NOT EXISTS idx_users_reset_token ON users (reset_password_token);", $pdo);
            $this->execute("CREATE INDEX IF NOT EXISTS idx_users_verification_token ON users (verification_token);", $pdo);
        } catch (\Throwable $e) {
            // Abaikan jika indeks sudah ada
        }
    }

    public function down(PDO $pdo): void
    {
        // Rollback opsional
    }
}
