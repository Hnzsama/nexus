<?php

declare(strict_types=1);

namespace Core\Database\Schema;

/**
 * Class Column
 * 
 * Merepresentasikan satu definisi kolom dalam tabel database.
 * Mendukung fluent modifiers seperti nullable(), default(), dan unique().
 */
class Column
{
    public bool $nullable = false;
    public mixed $default = null;
    public bool $hasDefault = false;
    public bool $unique = false;
    public bool $primary = false;
    public bool $autoIncrement = false;

    public function __construct(
        public string $name,
        public string $type,
        public ?int $length = null
    ) {}

    /**
     * Jadikan kolom boleh bernilai NULL.
     */
    public function nullable(bool $value = true): self
    {
        $this->nullable = $value;
        return $this;
    }

    /**
     * Tentukan nilai default untuk kolom ini.
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;
        return $this;
    }

    /**
     * Jadikan kolom ini memiliki index UNIQUE.
     */
    public function unique(bool $value = true): self
    {
        $this->unique = $value;
        return $this;
    }

    /**
     * Jadikan kolom ini Primary Key.
     */
    public function primary(bool $value = true): self
    {
        $this->primary = $value;
        return $this;
    }

    /**
     * Jadikan kolom ini Auto Incrementing.
     */
    public function autoIncrement(bool $value = true): self
    {
        $this->autoIncrement = $value;
        return $this;
    }
}
