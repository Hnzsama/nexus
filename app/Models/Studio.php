<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Studio
 * 
 * @property int $id
 * @property int $cinema_id
 * @property string $name
 * @property string $studio_type
 * @property int $capacity
 * @property string $created_at
 * @property string $updated_at
 */
class Studio extends Model
{
    protected string $table = 'studios';

    protected array $fillable = [
        'cinema_id',
        'name',
        'studio_type',
        'capacity',
    ];
}
