<?php

declare(strict_types=1);

use Core\Database\Migration;

/**
 * Class AddAvatarToUsersTable
 * 
 * Menambahkan kolom avatar untuk menyimpan path gambar profil pengguna.
 */
class AddAvatarToUsersTable extends Migration
{
    public function up(PDO $pdo): void
    {
        $this->execute("
            ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL;
        ", $pdo);
    }

    public function down(PDO $pdo): void
    {
        // Rollback opsional
    }
}
