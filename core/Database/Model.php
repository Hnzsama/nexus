<?php

declare(strict_types=1);

namespace Core\Database;

use JsonSerializable;
use RuntimeException;

/**
 * Class Model
 * 
 * Base Model berarsitektur Lightweight Active Record / DataMapper.
 * Menyediakan akses objek ke baris tabel database, mass-assignment protection ($fillable),
 * auto-timestamps, dirty checking pada update, serta integrasi seamless dengan QueryBuilder.
 */
abstract class Model implements JsonSerializable
{
    /**
     * Nama tabel database. Jika kosong, otomatis diambil dari nama class bentuk jamak.
     */
    protected string $table = '';

    /**
     * Nama kolom Primary Key.
     */
    protected string $primaryKey = 'id';

    /**
     * Kolom-kolom yang diizinkan untuk mass-assignment.
     *
     * @var array<int, string>
     */
    protected array $fillable = [];

    /**
     * Penanda apakah tabel memiliki kolom created_at dan updated_at otomatis.
     */
    protected bool $timestamps = true;

    /**
     * Nilai atribut saat ini.
     *
     * @var array<string, mixed>
     */
    protected array $attributes = [];

    /**
     * Snapshot nilai atribut asli saat pertama kali diambil dari database (untuk dirty checking).
     *
     * @var array<string, mixed>
     */
    protected array $original = [];

    /**
     * Penanda apakah model ini sudah tersimpan di database.
     */
    public bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /**
     * Dapatkan nama tabel yang terikat dengan model ini.
     */
    public function getTable(): string
    {
        if (!empty($this->table)) {
            return $this->table;
        }

        // Ambil nama class pendek: App\Models\User -> user -> users
        $shortName = (new \ReflectionClass($this))->getShortName();
        $snake = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

        // Pluralize sederhana (user -> users, category -> categories, product -> products)
        if (str_ends_with($snake, 'y') && !preg_match('/[aeiou]y$/', $snake)) {
            return substr($snake, 0, -1) . 'ies';
        }

        return $snake . 's';
    }

    /**
     * Dapatkan nama kolom Primary Key.
     */
    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    /**
     * Buat QueryBuilder baru yang terikat dengan model ini.
     */
    public static function query(): QueryBuilder
    {
        $instance = new static();
        return (new QueryBuilder($instance->getTable()))->asModel(static::class);
    }

    /**
     * Cari record berdasar Primary Key.
     *
     * @param int|string $id
     * @return static|null
     */
    public static function find(int|string $id): ?static
    {
        $instance = new static();
        return static::query()->where($instance->getPrimaryKey(), '=', $id)->first();
    }

    /**
     * Dapatkan seluruh data dari tabel.
     *
     * @return array<int, static>
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    /**
     * Buat dan simpan record baru sekaligus ke database.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Instansiasi model dari data hasil query database.
     *
     * @param array<string, mixed> $attributes
     * @return static
     */
    public static function hydrate(array $attributes): static
    {
        $model = new static();
        $model->attributes = $attributes;
        $model->original = $attributes;
        $model->exists = true;

        return $model;
    }

    /**
     * Isi atribut model dengan perlindungan $fillable.
     *
     * @param array<string, mixed> $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Cek apakah sebuah atribut diizinkan untuk mass-assignment.
     */
    protected function isFillable(string $key): bool
    {
        if (empty($this->fillable)) {
            return true;
        }
        return in_array($key, $this->fillable, true);
    }

    /**
     * Simpan model ke database (INSERT jika baru, UPDATE jika sudah ada).
     *
     * @return bool
     */
    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');

        // Kasus 1: Model Baru (INSERT)
        if (!$this->exists) {
            if ($this->timestamps) {
                $this->attributes['created_at'] ??= $now;
                $this->attributes['updated_at'] ??= $now;
            }

            $id = (new QueryBuilder($this->getTable()))->insert($this->attributes);
            $this->attributes[$this->primaryKey] = $id;
            $this->original = $this->attributes;
            $this->exists = true;

            return true;
        }

        // Kasus 2: Model Tersimpan (UPDATE dirty attributes)
        $dirty = $this->getDirty();
        if (empty($dirty)) {
            return true; // Tidak ada perubahan
        }

        if ($this->timestamps) {
            $dirty['updated_at'] = $now;
            $this->attributes['updated_at'] = $now;
        }

        $affected = (new QueryBuilder($this->getTable()))
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->update($dirty);

        $this->original = $this->attributes;
        return $affected > 0;
    }

    /**
     * Update atribut model dan langsung simpan ke database.
     *
     * @param array<string, mixed> $attributes
     * @return bool
     */
    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        return $this->save();
    }

    /**
     * Hapus record model saat ini dari database.
     *
     * @return bool
     */
    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        $affected = (new QueryBuilder($this->getTable()))
            ->where($this->primaryKey, '=', $this->attributes[$this->primaryKey])
            ->delete();

        $this->exists = false;
        return $affected > 0;
    }

    /**
     * Dapatkan daftar atribut yang nilainya berubah sejak pertama kali diambil.
     *
     * @return array<string, mixed>
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Dapatkan seluruh data atribut model dalam bentuk array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Serialisasi JSON untuk return di controller.
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /* -------------------------------------------------------------
     * Magic Methods untuk Akses Properti & Static Chaining Query
     * ------------------------------------------------------------- */

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }

    /**
     * Teruskan pemanggilan method statis (misal: Model::where(), Model::latest()) ke QueryBuilder.
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        return static::query()->{$method}(...$parameters);
    }
}
