<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use RuntimeException;

/**
 * Class Migrator
 * 
 * Pengelola migrasi database Nexus.
 * Bertanggung jawab mengecek status tabel migrations, melacak batch eksekusi,
 * menjalankan migration up(), serta melakukan rollback down().
 */
class Migrator
{
    protected PDO $pdo;
    protected string $migrationsPath;

    public function __construct(?PDO $pdo = null, ?string $migrationsPath = null)
    {
        $this->pdo = $pdo ?? Connection::getPdo();
        $this->migrationsPath = $migrationsPath ?? (function_exists('base_path') ? base_path('database/migrations') : dirname(__DIR__, 2) . '/database/migrations');
    }

    /**
     * Pastikan tabel pelacak 'migrations' tersedia di database.
     */
    public function ensureMigrationsTable(): void
    {
        \Core\Database\Schema\Schema::create('migrations', function (\Core\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('migration');
            $table->integer('batch');
            $table->dateTime('executed_at');
        }, $this->pdo);
    }

    /**
     * Jalankan seluruh migrasi yang belum dieksekusi.
     *
     * @return array<int, string> Daftar file migrasi yang berhasil dijalankan
     */
    public function run(): array
    {
        $this->ensureMigrationsTable();

        $allFiles = $this->getMigrationFiles();
        $ranMigrations = $this->getRanMigrations();

        $pending = array_diff($allFiles, $ranMigrations);

        if (empty($pending)) {
            return [];
        }

        $batch = $this->getNextBatchNumber();
        $migrated = [];

        foreach ($pending as $file) {
            $filePath = $this->migrationsPath . '/' . $file . '.php';

            if (!file_exists($filePath)) {
                continue;
            }

            require_once $filePath;

            $className = $this->resolveClassName($file);
            if (!class_exists($className)) {
                throw new RuntimeException("Class migrasi [{$className}] tidak ditemukan di dalam [{$file}.php].");
            }

            /** @var Migration $migration */
            $migration = new $className();
            $migration->up($this->pdo);

            // Catat ke tabel migrations
            $stmt = $this->pdo->prepare("INSERT INTO migrations (migration, batch, executed_at) VALUES (?, ?, ?)");
            $stmt->execute([$file, $batch, date('Y-m-d H:i:s')]);

            $migrated[] = $file;
        }

        return $migrated;
    }

    /**
     * Batalkan (rollback) migrasi dari batch terakhir.
     *
     * @return array<int, string> Daftar file migrasi yang di-rollback
     */
    public function rollback(): array
    {
        $this->ensureMigrationsTable();

        $lastBatch = $this->getLastBatchNumber();
        if ($lastBatch === 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC");
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $rolledBack = [];

        foreach ($migrations as $file) {
            $filePath = $this->migrationsPath . '/' . $file . '.php';

            if (file_exists($filePath)) {
                require_once $filePath;
                $className = $this->resolveClassName($file);

                if (class_exists($className)) {
                    /** @var Migration $migration */
                    $migration = new $className();
                    $migration->down($this->pdo);
                }
            }

            // Hapus record dari tabel migrations
            $delStmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
            $delStmt->execute([$file]);

            $rolledBack[] = $file;
        }

        return $rolledBack;
    }

    /**
     * Dapatkan status seluruh file migrasi (Ran atau Pending).
     *
     * @return array<int, array{migration: string, ran: bool, batch: ?int, executed_at: ?string}>
     */
    public function status(): array
    {
        $this->ensureMigrationsTable();

        $allFiles = $this->getMigrationFiles();

        $stmt = $this->pdo->query("SELECT migration, batch, executed_at FROM migrations");
        $ranData = [];
        while ($row = $stmt->fetch()) {
            $ranData[$row['migration']] = [
                'batch' => (int) $row['batch'],
                'executed_at' => $row['executed_at'],
            ];
        }

        $statusList = [];
        foreach ($allFiles as $file) {
            $isRan = isset($ranData[$file]);
            $statusList[] = [
                'migration' => $file,
                'ran' => $isRan,
                'batch' => $isRan ? $ranData[$file]['batch'] : null,
                'executed_at' => $isRan ? $ranData[$file]['executed_at'] : null,
            ];
        }

        return $statusList;
    }

    /**
     * Dapatkan seluruh nama file migrasi yang ada di direktori database/migrations.
     *
     * @return array<int, string>
     */
    protected function getMigrationFiles(): array
    {
        if (!is_dir($this->migrationsPath)) {
            mkdir($this->migrationsPath, 0755, true);
            return [];
        }

        $files = glob($this->migrationsPath . '/*.php');
        if ($files === false) {
            return [];
        }

        $names = array_map(fn($f) => basename($f, '.php'), $files);
        sort($names);

        return $names;
    }

    /**
     * Dapatkan daftar nama migrasi yang sudah tercatat di database.
     *
     * @return array<int, string>
     */
    protected function getRanMigrations(): array
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Dapatkan nomor batch berikutnya.
     */
    protected function getNextBatchNumber(): int
    {
        return $this->getLastBatchNumber() + 1;
    }

    /**
     * Dapatkan nomor batch terakhir.
     */
    protected function getLastBatchNumber(): int
    {
        $stmt = $this->pdo->query("SELECT MAX(batch) as max_batch FROM migrations");
        $row = $stmt->fetch();
        return (int) ($row['max_batch'] ?? 0);
    }

    /**
     * Ubah nama file migrasi menjadi nama class PHP.
     * Contoh: 2026_10_14_000001_create_users_table -> CreateUsersTable
     */
    protected function resolveClassName(string $fileName): string
    {
        // Hilangkan prefix tanggal: 2026_10_14_000001_
        $name = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $fileName);

        // Convert snake_case ke PascalCase
        return str_replace(' ', '', ucwords(str_replace('_', ' ', (string) $name)));
    }
}
