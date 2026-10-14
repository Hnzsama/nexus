<?php

declare(strict_types=1);

namespace Core;

/**
 * Class Env
 * 
 * Native .env parser murni tanpa dependensi eksternal.
 * Bertanggung jawab mem-parsing file .env, menangani komentar, kutip,
 * interpolasi variabel, serta casting tipe data primitif.
 */
class Env
{
    /**
     * Cache variabel environment yang telah diparsing secara typed.
     *
     * @var array<string, mixed>
     */
    protected static array $variables = [];

    /**
     * Memuat dan mem-parsing file .env ke dalam environment PHP.
     *
     * @param string $filePath Path absolut atau relatif ke file .env
     * @param bool $override Apakah menimpa variabel yang sudah ada sebelumnya
     * @return bool
     */
    public static function load(string $filePath, bool $override = true): bool
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati baris kosong atau baris komentar penuh
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Hapus awalan 'export ' jika ada (format shell env)
            if (str_starts_with($line, 'export ')) {
                $line = substr($line, 7);
            }

            // Pisahkan key dan raw value berdasar '=' pertama
            $separatorPos = strpos($line, '=');
            if ($separatorPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $separatorPos));
            $rawValue = trim(substr($line, $separatorPos + 1));

            if ($key === '') {
                continue;
            }

            // Parsing nilai string mentah menjadi nilai terformat
            $parsedValue = self::parseValue($rawValue);

            // Simpan jika diperbolehkan menimpa atau belum diset sebelumnya
            if ($override || !array_key_exists($key, self::$variables)) {
                self::set($key, $parsedValue);
            }
        }

        return true;
    }

    /**
     * Parsing nilai mentah: menangani tanda kutip, komentar inline, escape character,
     * serta konversi tipe khusus (boolean, null).
     *
     * @param string $value
     * @return mixed
     */
    protected static function parseValue(string $value): mixed
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $firstChar = $value[0];
        $lastChar = substr($value, -1);

        // Kasus 1: Nilai diapit tanda kutip ganda ("...")
        if ($firstChar === '"' && $lastChar === '"' && strlen($value) >= 2) {
            $unquoted = substr($value, 1, -1);
            // Ganti escape sequence seperti \n, \r, \t, \"
            $unquoted = str_replace(
                ['\\n', '\\r', '\\t', '\\"', '\\\\'],
                ["\n", "\r", "\t", '"', '\\'],
                $unquoted
            );
            return self::interpolateVariables($unquoted);
        }

        // Kasus 2: Nilai diapit tanda kutip tunggal ('...')
        if ($firstChar === "'" && $lastChar === "'" && strlen($value) >= 2) {
            // Tanda kutip tunggal diperlakukan sebagai string literal murni
            return substr($value, 1, -1);
        }

        // Kasus 3: Nilai tanpa kutip (unquoted)
        // Tangani komentar inline (misal: APP_PORT=8000 # Server port)
        $commentPos = strpos($value, ' #');
        if ($commentPos !== false) {
            $value = trim(substr($value, 0, $commentPos));
        }

        // Interpolasi variabel sebelum konversi tipe
        $value = self::interpolateVariables($value);

        // Konversi tipe data khusus
        $lower = strtolower($value);
        return match ($lower) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => $value,
        };
    }

    /**
     * Melakukan interpolasi variabel bertipe ${VAR_NAME} atau $VAR_NAME
     * dengan nilai yang sudah terdaftar di environment.
     *
     * @param string $value
     * @return string
     */
    protected static function interpolateVariables(string $value): string
    {
        return preg_replace_callback(
            '/\${([A-Za-z0-9_]+)}|\$([A-Za-z0-9_]+)/',
            function (array $matches) {
                $varName = !empty($matches[1]) ? $matches[1] : $matches[2];
                return (string) self::get($varName, '');
            },
            $value
        ) ?? $value;
    }

    /**
     * Daftarkan key & value ke runtime environment PHP ($_ENV, $_SERVER, putenv, dan static cache).
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::$variables[$key] = $value;
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;

        // String representation untuk putenv()
        $stringValue = match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_null($value) => '',
            default => (string) $value,
        };

        putenv("{$key}={$stringValue}");
    }

    /**
     * Ambil nilai dari environment dengan fallback nilai default.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, self::$variables)) {
            return self::$variables[$key];
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        $fromEnv = getenv($key);
        if ($fromEnv !== false) {
            return $fromEnv;
        }

        return $default;
    }

    /**
     * Dapatkan semua variabel yang telah diparsing.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return self::$variables;
    }
}
