<?php

declare(strict_types=1);

namespace Core\Database\Schema;

use Closure;
use Core\Database\Connection;
use PDO;

/**
 * Class Schema
 * 
 * Facade ringan Schema Builder untuk definisi tabel database multi-driver.
 */
class Schema
{
    /**
     * Buat tabel baru dengan Blueprint deklaratif.
     *
     * @param string $table
     * @param Closure(Blueprint): void $callback
     * @param PDO|null $pdo
     */
    public static function create(string $table, Closure $callback, ?PDO $pdo = null): void
    {
        $pdo ??= Connection::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $blueprint = new Blueprint($table);
        $callback($blueprint);

        $sql = $blueprint->toSql($driver);
        $pdo->exec($sql);
    }

    /**
     * Hapus tabel jika sudah ada.
     */
    public static function dropIfExists(string $table, ?PDO $pdo = null): void
    {
        $pdo ??= Connection::getPdo();
        $pdo->exec("DROP TABLE IF EXISTS {$table};");
    }

    /**
     * Cek apakah tabel tertentu sudah ada di database.
     */
    public static function hasTable(string $table, ?PDO $pdo = null): bool
    {
        $pdo ??= Connection::getPdo();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        $sql = match ($driver) {
            'sqlite' => "SELECT count(*) FROM sqlite_master WHERE type='table' AND name = ?",
            'pgsql' => "SELECT count(*) FROM information_schema.tables WHERE table_name = ?",
            default => "SELECT count(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?",
        };

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$table]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
