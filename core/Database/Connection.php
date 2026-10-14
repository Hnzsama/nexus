<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * Class Connection
 * 
 * Pengelola koneksi database berbasis PDO Singleton.
 * Mendukung SQLite, MySQL, dan PostgreSQL dengan konfigurasi otomatis,
 * auto-creation SQLite database file, serta manajemen transaksi database.
 */
class Connection
{
    /**
     * Cache instance PDO aktif berdasar nama koneksi.
     *
     * @var array<string, PDO>
     */
    protected static array $instances = [];

    /**
     * Dapatkan instance PDO koneksi database.
     *
     * @param string|null $name Nama koneksi ('sqlite', 'mysql', 'pgsql') atau null untuk default
     * @return PDO
     */
    public static function getPdo(?string $name = null): PDO
    {
        $configFile = function_exists('base_path') ? base_path('config/database.php') : dirname(__DIR__, 2) . '/config/database.php';
        $config = file_exists($configFile) ? require $configFile : [];

        $connectionName = $name ?? $config['default'] ?? (function_exists('env') ? env('DB_CONNECTION', 'sqlite') : 'sqlite');

        if (isset(self::$instances[$connectionName])) {
            return self::$instances[$connectionName];
        }

        $connectionConfig = $config['connections'][$connectionName] ?? self::getDefaultConfig($connectionName);

        self::$instances[$connectionName] = self::createPdoInstance($connectionName, $connectionConfig);
        return self::$instances[$connectionName];
    }

    /**
     * Buat koneksi PDO baru berdasar driver yang dipilih.
     *
     * @param string $driver
     * @param array<string, mixed> $config
     * @return PDO
     */
    protected static function createPdoInstance(string $driver, array $config): PDO
    {
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            switch ($driver) {
                case 'sqlite':
                    $database = $config['database'] ?? (function_exists('base_path') ? base_path('database/nexus.sqlite') : 'database/nexus.sqlite');
                    
                    // Jika path relatif, sesuaikan dengan root framework
                    if ($database !== ':memory:' && !str_starts_with($database, '/')) {
                        $database = function_exists('base_path') ? base_path($database) : dirname(__DIR__, 2) . '/' . $database;
                    }

                    // Otomatis buat direktori dan file sqlite jika belum ada
                    if ($database !== ':memory:') {
                        $dir = dirname($database);
                        if (!is_dir($dir)) {
                            mkdir($dir, 0755, true);
                        }
                        if (!file_exists($database)) {
                            touch($database);
                        }
                    }

                    $pdo = new PDO("sqlite:{$database}", null, null, $defaultOptions);

                    if (!empty($config['foreign_key_constraints'])) {
                        $pdo->exec('PRAGMA foreign_keys = ON;');
                    }
                    return $pdo;

                case 'mysql':
                    $host = $config['host'] ?? '127.0.0.1';
                    $port = $config['port'] ?? 3306;
                    $db = $config['database'] ?? 'nexus_db';
                    $charset = $config['charset'] ?? 'utf8mb4';
                    $user = $config['username'] ?? 'root';
                    $pass = $config['password'] ?? '';

                    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
                    $options = array_replace($defaultOptions, $config['options'] ?? []);

                    return new PDO($dsn, $user, $pass, $options);

                case 'pgsql':
                    $host = $config['host'] ?? '127.0.0.1';
                    $port = $config['port'] ?? 5432;
                    $db = $config['database'] ?? 'nexus_db';
                    $user = $config['username'] ?? 'postgres';
                    $pass = $config['password'] ?? '';

                    $dsn = "pgsql:host={$host};port={$port};dbname={$db}";
                    return new PDO($dsn, $user, $pass, $defaultOptions);

                default:
                    throw new RuntimeException("Driver database [{$driver}] tidak didukung.");
            }
        } catch (PDOException $e) {
            throw new RuntimeException("Koneksi database gagal [{$driver}]: " . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Fallback konfigurasi jika config/database.php belum termuat.
     *
     * @param string $driver
     * @return array<string, mixed>
     */
    protected static function getDefaultConfig(string $driver): array
    {
        return match ($driver) {
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => function_exists('env') ? env('DB_DATABASE', 'database/nexus.sqlite') : 'database/nexus.sqlite',
                'foreign_key_constraints' => true,
            ],
            'mysql' => [
                'driver' => 'mysql',
                'host' => function_exists('env') ? env('DB_HOST', '127.0.0.1') : '127.0.0.1',
                'port' => (int) (function_exists('env') ? env('DB_PORT', 3306) : 3306),
                'database' => function_exists('env') ? env('DB_DATABASE', 'nexus_db') : 'nexus_db',
                'username' => function_exists('env') ? env('DB_USERNAME', 'root') : 'root',
                'password' => function_exists('env') ? env('DB_PASSWORD', '') : '',
                'charset' => 'utf8mb4',
            ],
            default => [
                'driver' => $driver,
            ]
        };
    }

    /**
     * Jalankan query callback di dalam database transaction atomik.
     *
     * @template T
     * @param callable(PDO): T $callback
     * @param string|null $connection
     * @return T
     * @throws Throwable
     */
    public static function transaction(callable $callback, ?string $connection = null): mixed
    {
        $pdo = self::getPdo($connection);
        $pdo->beginTransaction();

        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reset seluruh koneksi database yang tersimpan (misal: untuk CLI atau testing).
     */
    public static function disconnect(): void
    {
        self::$instances = [];
    }
}
