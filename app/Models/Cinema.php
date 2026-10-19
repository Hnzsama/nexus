<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Cinema
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $city
 * @property string $address
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class Cinema extends Model
{
    protected string $table = 'cinemas';

    protected array $fillable = [
        'code',
        'name',
        'city',
        'address',
        'is_active',
    ];
}
