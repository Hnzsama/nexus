<?php

declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Class Blueprint
 * 
 * Skema builder tabel database.
 * Menyusun definisi kolom secara deklaratif dan meng-compile SQL DDL
 * sesuai dialek database target (SQLite, MySQL, PostgreSQL) secara otomatis.
 */
class Blueprint
{
    /**
     * @var array<int, Column>
     */
    protected array $columns = [];

    public function __construct(protected string $table) {}

    /**
     * Buat kolom Auto Incrementing Primary Key.
     */
    public function id(string $name = 'id'): Column
    {
        $column = new Column($name, 'id');
        $column->primary()->autoIncrement();
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom VARCHAR.
     */
    public function string(string $name, int $length = 255): Column
    {
        $column = new Column($name, 'string', $length);
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom TEXT.
     */
    public function text(string $name): Column
    {
        $column = new Column($name, 'text');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom INTEGER.
     */
    public function integer(string $name): Column
    {
        $column = new Column($name, 'integer');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom BIGINT.
     */
    public function bigInteger(string $name): Column
    {
        $column = new Column($name, 'bigInteger');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom BOOLEAN.
     */
    public function boolean(string $name): Column
    {
        $column = new Column($name, 'boolean');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom DATETIME.
     */
    public function dateTime(string $name): Column
    {
        $column = new Column($name, 'dateTime');
        $this->columns[] = $column;
        return $column;
    }

    /**
     * Buat kolom created_at dan updated_at secara otomatis.
     */
    public function timestamps(): void
    {
        $this->dateTime('created_at')->nullable(false);
        $this->dateTime('updated_at')->nullable(false);
    }

    /**
     * Compile definisi Blueprint menjadi sintaks CREATE TABLE sesuai dialek driver.
     *
     * @param string $driver 'sqlite', 'mysql', 'pgsql'
     * @return string
     */
    public function toSql(string $driver): string
    {
        $definitions = [];

        foreach ($this->columns as $column) {
            $definitions[] = $this->compileColumn($column, $driver);
        }

        $body = implode(",\n    ", $definitions);

        return match ($driver) {
            'sqlite' => "CREATE TABLE IF NOT EXISTS {$this->table} (\n    {$body}\n);",
            'pgsql' => "CREATE TABLE IF NOT EXISTS {$this->table} (\n    {$body}\n);",
            default => "CREATE TABLE IF NOT EXISTS {$this->table} (\n    {$body}\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
        };
    }

    /**
     * Compile satu kolom ke SQL berdasar driver.
     */
    protected function compileColumn(Column $column, string $driver): string
    {
        // Penanganan khusus ID (Auto Incrementing Primary Key)
        if ($column->type === 'id') {
            return match ($driver) {
                'sqlite' => "{$column->name} INTEGER PRIMARY KEY AUTOINCREMENT",
                'pgsql' => "{$column->name} SERIAL PRIMARY KEY",
                default => "{$column->name} INT AUTO_INCREMENT PRIMARY KEY",
            };
        }

        // Tipe data kolom
        $type = match ($column->type) {
            'string' => "VARCHAR(" . ($column->length ?? 255) . ")",
            'text' => "TEXT",
            'integer' => match ($driver) {
                'sqlite' => "INTEGER",
                default => "INT",
            },
            'bigInteger' => match ($driver) {
                'sqlite' => "INTEGER",
                default => "BIGINT",
            },
            'boolean' => match ($driver) {
                'sqlite' => "INTEGER",
                'pgsql' => "BOOLEAN",
                default => "TINYINT(1)",
            },
            'dateTime' => match ($driver) {
                'pgsql' => "TIMESTAMP",
                default => "DATETIME",
            },
            default => "VARCHAR(255)",
        };

        $sql = "{$column->name} {$type}";

        // Nullable modifier
        $sql .= $column->nullable ? " NULL" : " NOT NULL";

        // Default modifier
        if ($column->hasDefault) {
            $default = $column->default;
            if (is_bool($default)) {
                $default = $default ? ($driver === 'pgsql' ? 'TRUE' : '1') : ($driver === 'pgsql' ? 'FALSE' : '0');
            } elseif (is_string($default)) {
                $default = "'{$default}'";
            } elseif (is_null($default)) {
                $default = 'NULL';
            }
            $sql .= " DEFAULT {$default}";
        }

        // Unique modifier
        if ($column->unique) {
            $sql .= " UNIQUE";
        }

        return $sql;
    }
}
