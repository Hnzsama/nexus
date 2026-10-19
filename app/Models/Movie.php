<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Movie
 * 
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $synopsis
 * @property int $duration_minutes
 * @property string $age_rating
 * @property string|null $poster_url
 * @property string|null $trailer_url
 * @property string $status
 * @property string $created_at
 * @property string $updated_at
 */
class Movie extends Model
{
    protected string $table = 'movies';

    protected array $fillable = [
        'title',
        'slug',
        'synopsis',
        'duration_minutes',
        'age_rating',
        'poster_url',
        'trailer_url',
        'status',
    ];
}
