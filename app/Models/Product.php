<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = [
        // Masukkan kolom yang diizinkan untuk mass assignment
    ];
}