<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Showtime
 * 
 * @property int $id
 * @property int $movie_id
 * @property int $studio_id
 * @property string $start_time
 * @property string $end_time
 * @property float $base_price
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Showtime extends Model
{
    protected string $table = 'showtimes';

    protected array $fillable = [
        'movie_id',
        'studio_id',
        'start_time',
        'end_time',
        'base_price',
        'status',
    ];
}
