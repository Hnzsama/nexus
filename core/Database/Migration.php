<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

/**
 * Class Migration
 * 
 * Kontrak dasar untuk seluruh file skema migrasi database.
 * Menyediakan method execute() dengan Dialect Normalizer cerdas:
 * Pengembang cukup menulis SATU query SQL standar, dan framework otomatis
 * menyesuaikan perbedaan sintaks (Auto-increment, Datetime, Engine, Types)
 * untuk SQLite, MySQL, dan PostgreSQL tanpa perlu if/match berulang!
 */
abstract class Migration
{
    /**
     * Jalankan perubahan skema (buat tabel, tambah kolom, index, dll.).
     */
    abstract public function up(PDO $pdo): void;

    /**
     * Kembalikan/batalkan perubahan skema (drop tabel, rollback kolom, dll.).
     */
    abstract public function down(PDO $pdo): void;

    /**
     * Eksekusi raw SQL query dengan normalisasi otomatis antar dialek DBMS.
     * Cukup tulis SQL standar sekali saja (misal: INT AUTO_INCREMENT PRIMARY KEY atau SERIAL PRIMARY KEY).
     *
     * @param string $sql Query SQL murni
     * @param PDO|null $pdo Instance PDO aktif
     * @return int Jumlah baris terpengaruh / status eksekusi
     */
    public function execute(string $sql, ?PDO $pdo = null): int
    {
        $pdo ??= Connection::getPdo();
        $driver = strtolower((string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        $normalizedSql = self::normalizeSql($sql, $driver);

        return (int) $pdo->exec($normalizedSql);
    }

    /**
     * Normalisasi query SQL DDL agar kompatibel di berbagai DBMS secara otomatis.
     *
     * @param string $sql
     * @param string $driver 'sqlite', 'mysql', 'pgsql'
     * @return string
     */
    public static function normalizeSql(string $sql, string $driver): string
    {
        // Hapus komentar SQL satu baris (-- ...) untuk mencegah comment bleed
        $normalized = preg_replace('/--.*$/m', '', $sql) ?? $sql;


        switch ($driver) {
            case 'sqlite':
                // 1. Auto Increment Primary Key: Ubah ke INTEGER PRIMARY KEY AUTOINCREMENT
                $normalized = preg_replace(
                    '/\b(INT|BIGINT|SMALLINT|TINYINT)\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i',
                    'INTEGER PRIMARY KEY AUTOINCREMENT',
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace(
                    '/\b(SERIAL|BIGSERIAL)\s+PRIMARY\s+KEY\b/i',
                    'INTEGER PRIMARY KEY AUTOINCREMENT',
                    $normalized
                ) ?? $normalized;

                // 2. Hapus MySQL Table Options (ENGINE=InnoDB, DEFAULT CHARSET, COLLATE)
                $normalized = preg_replace('/\s+ENGINE\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/\s+COLLATE\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;

                // 3. Tipe data boolean MySQL -> INTEGER di SQLite
                $normalized = preg_replace('/\bTINYINT\s*\(\s*1\s*\)/i', 'INTEGER', $normalized) ?? $normalized;
                break;

            case 'pgsql':
                // 1. Auto Increment Primary Key: Ubah ke SERIAL PRIMARY KEY
                $normalized = preg_replace(
                    '/\bINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i',
                    'SERIAL PRIMARY KEY',
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace(
                    '/\bBIGINT\s+AUTO_INCREMENT\s+PRIMARY\s+KEY\b/i',
                    'BIGSERIAL PRIMARY KEY',
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace(
                    '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                    'SERIAL PRIMARY KEY',
                    $normalized
                ) ?? $normalized;

                // 2. DATETIME -> TIMESTAMP di PostgreSQL
                $normalized = preg_replace('/\bDATETIME\b/i', 'TIMESTAMP', $normalized) ?? $normalized;

                // 3. TINYINT(1) -> BOOLEAN
                $normalized = preg_replace('/\bTINYINT\s*\(\s*1\s*\)/i', 'BOOLEAN', $normalized) ?? $normalized;

                // 4. Hapus MySQL Table Options
                $normalized = preg_replace('/\s+ENGINE\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/\s+DEFAULT\s+CHARSET\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;
                $normalized = preg_replace('/\s+COLLATE\s*=\s*[a-zA-Z0-9_]+/i', '', $normalized) ?? $normalized;
                break;

            case 'mysql':
            default:
                // 1. SQLite atau PgSQL syntax -> INT AUTO_INCREMENT PRIMARY KEY
                $normalized = preg_replace(
                    '/\bINTEGER\s+PRIMARY\s+KEY\s+AUTOINCREMENT\b/i',
                    'INT AUTO_INCREMENT PRIMARY KEY',
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace(
                    '/\bSERIAL\s+PRIMARY\s+KEY\b/i',
                    'INT AUTO_INCREMENT PRIMARY KEY',
                    $normalized
                ) ?? $normalized;

                $normalized = preg_replace(
                    '/\bBIGSERIAL\s+PRIMARY\s+KEY\b/i',
                    'BIGINT AUTO_INCREMENT PRIMARY KEY',
                    $normalized
                ) ?? $normalized;
                break;
        }

        return trim($normalized);
    }
}
