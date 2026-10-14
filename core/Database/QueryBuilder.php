<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Class QueryBuilder
 * 
 * Lightweight Fluent Query Builder berbasis PDO Prepared Statements.
 * Mencegah serangan SQL Injection secara default, mendukung chaining query yang intuitif,
 * serta integrasi dengan Model Active Record.
 */
class QueryBuilder
{
    protected PDO $pdo;
    protected string $table = '';
    protected array $columns = ['*'];
    protected array $wheres = [];
    protected array $joins = [];
    protected array $orders = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected array $bindings = [];

    /**
     * Nama class model untuk hidrasi hasil query (jika ada).
     *
     * @var class-string|null
     */
    protected ?string $modelClass = null;

    public function __construct(string $table = '', ?PDO $pdo = null)
    {
        $this->table = $table;
        $this->pdo = $pdo ?? Connection::getPdo();
    }

    /**
     * Static factory untuk memulai query pada sebuah tabel.
     */
    public static function table(string $table, ?PDO $pdo = null): self
    {
        return new self($table, $pdo);
    }

    /**
     * Set nama tabel target query.
     */
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Tentukan kolom yang ingin dipilih.
     *
     * @param string|array<int, string> $columns
     */
    public function select(string|array $columns = ['*']): self
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();
        return $this;
    }

    /**
     * Tambahkan kondisi WHERE (AND).
     */
    public function where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        // Jika dipanggil dengan 2 argumen: where('status', 'active') -> operator default '='
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => strtoupper((string) $operator),
            'value' => $value,
            'boolean' => strtoupper($boolean),
        ];

        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Tambahkan kondisi WHERE (OR).
     */
    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Tambahkan kondisi WHERE IN (...).
     *
     * @param string $column
     * @param array<int, mixed> $values
     * @param string $boolean
     */
    public function whereIn(string $column, array $values, string $boolean = 'AND'): self
    {
        if (empty($values)) {
            $this->wheres[] = [
                'type' => 'raw',
                'sql' => '0 = 1',
                'boolean' => strtoupper($boolean),
            ];
            return $this;
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'not' => false,
            'boolean' => strtoupper($boolean),
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Tambahkan kondisi WHERE NOT IN (...).
     */
    public function whereNotIn(string $column, array $values, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'in',
            'column' => $column,
            'values' => $values,
            'not' => true,
            'boolean' => strtoupper($boolean),
        ];

        foreach ($values as $val) {
            $this->bindings[] = $val;
        }

        return $this;
    }

    /**
     * Tambahkan kondisi WHERE IS NULL.
     */
    public function whereNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => false,
            'boolean' => strtoupper($boolean),
        ];
        return $this;
    }

    /**
     * Tambahkan kondisi WHERE IS NOT NULL.
     */
    public function whereNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => $column,
            'not' => true,
            'boolean' => strtoupper($boolean),
        ];
        return $this;
    }

    /**
     * Tambahkan INNER JOIN.
     */
    public function join(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'INNER',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    /**
     * Tambahkan LEFT JOIN.
     */
    public function leftJoin(string $table, string $first, string $operator, string $second): self
    {
        $this->joins[] = [
            'type' => 'LEFT',
            'table' => $table,
            'first' => $first,
            'operator' => $operator,
            'second' => $second,
        ];
        return $this;
    }

    /**
     * Tambahkan ORDER BY.
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Shortcut untuk mengurutkan berdasar waktu terbaru (DESC).
     */
    public function latest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Shortcut untuk mengurutkan berdasar waktu terlama (ASC).
     */
    public function oldest(string $column = 'created_at'): self
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Batasi jumlah baris yang diambil.
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Lewati sejumlah baris awal.
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Ikat class model untuk hidrasi data hasil query.
     *
     * @param class-string|null $modelClass
     */
    public function asModel(?string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    /**
     * Generate SQL string SELECT statement lengkap.
     */
    public function toSql(): string
    {
        $columns = implode(', ', $this->columns);
        $sql = "SELECT {$columns} FROM {$this->table}";

        // Joins
        if (!empty($this->joins)) {
            foreach ($this->joins as $j) {
                $sql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        // Wheres
        if (!empty($this->wheres)) {
            $whereParts = [];
            foreach ($this->wheres as $index => $w) {
                $prefix = $index === 0 ? '' : " {$w['boolean']} ";

                if ($w['type'] === 'basic') {
                    $whereParts[] = "{$prefix}{$w['column']} {$w['operator']} ?";
                } elseif ($w['type'] === 'in') {
                    $placeholders = implode(', ', array_fill(0, count($w['values']), '?'));
                    $operator = $w['not'] ? 'NOT IN' : 'IN';
                    $whereParts[] = "{$prefix}{$w['column']} {$operator} ({$placeholders})";
                } elseif ($w['type'] === 'null') {
                    $operator = $w['not'] ? 'IS NOT NULL' : 'IS NULL';
                    $whereParts[] = "{$prefix}{$w['column']} {$operator}";
                } elseif ($w['type'] === 'raw') {
                    $whereParts[] = "{$prefix}{$w['sql']}";
                }
            }
            $sql .= ' WHERE ' . implode('', $whereParts);
        }

        // Order By
        if (!empty($this->orders)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        // Limit & Offset
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Dapatkan daftar parameter bindings aktif.
     *
     * @return array<int, mixed>
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Eksekusi query dan dapatkan seluruh baris data.
     *
     * @return array<int, mixed>
     */
    public function get(): array
    {
        $stmt = $this->executeStatement($this->toSql(), $this->bindings);
        $rows = $stmt->fetchAll();

        if ($this->modelClass !== null && class_exists($this->modelClass)) {
            $class = $this->modelClass;
            return array_map(fn($row) => $class::hydrate($row), $rows);
        }

        return $rows;
    }

    /**
     * Eksekusi query dan dapatkan satu baris pertama.
     */
    public function first(): mixed
    {
        $this->limit(1);
        $results = $this->get();
        return $results[0] ?? null;
    }

    /**
     * Cari satu baris record berdasar Primary Key.
     */
    public function find(int|string $id, string $primaryKey = 'id'): mixed
    {
        return $this->where($primaryKey, '=', $id)->first();
    }

    /**
     * Hitung total baris yang cocok dengan kriteria query.
     */
    public function count(string $column = '*'): int
    {
        $this->columns = ["COUNT({$column}) as aggregate"];
        $stmt = $this->executeStatement($this->toSql(), $this->bindings);
        $row = $stmt->fetch();
        return (int) ($row['aggregate'] ?? 0);
    }

    /**
     * Cek apakah terdapat setidaknya satu baris data.
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Insert satu baris data baru ke tabel.
     *
     * @param array<string, mixed> $values
     * @return int|string ID record baru yang dibuat (lastInsertId)
     */
    public function insert(array $values): int|string
    {
        if (empty($values)) {
            throw new RuntimeException("Tidak ada data yang diberikan untuk insert.");
        }

        $columns = implode(', ', array_keys($values));
        $placeholders = implode(', ', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $this->executeStatement($sql, array_values($values));

        return $this->pdo->lastInsertId();
    }

    /**
     * Update data pada baris yang memenuhi kondisi WHERE.
     *
     * @param array<string, mixed> $values
     * @return int Jumlah baris yang terpengaruh (affected rows)
     */
    public function update(array $values): int
    {
        if (empty($values)) {
            return 0;
        }

        $setParts = [];
        $updateBindings = [];

        foreach ($values as $column => $value) {
            $setParts[] = "{$column} = ?";
            $updateBindings[] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $setParts);

        // Gabungkan WHERE clause
        if (!empty($this->wheres)) {
            $whereParts = [];
            foreach ($this->wheres as $index => $w) {
                $prefix = $index === 0 ? '' : " {$w['boolean']} ";
                if ($w['type'] === 'basic') {
                    $whereParts[] = "{$prefix}{$w['column']} {$w['operator']} ?";
                }
            }
            $sql .= ' WHERE ' . implode('', $whereParts);
        }

        $allBindings = array_merge($updateBindings, $this->bindings);
        $stmt = $this->executeStatement($sql, $allBindings);

        return $stmt->rowCount();
    }

    /**
     * Hapus data baris yang memenuhi kondisi WHERE.
     *
     * @return int Jumlah baris yang terhapus
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";

        if (!empty($this->wheres)) {
            $whereParts = [];
            foreach ($this->wheres as $index => $w) {
                $prefix = $index === 0 ? '' : " {$w['boolean']} ";
                if ($w['type'] === 'basic') {
                    $whereParts[] = "{$prefix}{$w['column']} {$w['operator']} ?";
                }
            }
            $sql .= ' WHERE ' . implode('', $whereParts);
        }

        $stmt = $this->executeStatement($sql, $this->bindings);
        return $stmt->rowCount();
    }

    /**
     * Eksekusi raw prepared statement secara aman.
     *
     * @param string $sql
     * @param array<int, mixed> $bindings
     * @return PDOStatement
     */
    protected function executeStatement(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($bindings);
        return $stmt;
    }
}
