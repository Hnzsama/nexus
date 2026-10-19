<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class SeatHold
 * 
 * Model state penguncian sementara kursi (Pessimistic Lock & Time-to-Live).
 * 
 * @property int $id
 * @property int $showtime_id
 * @property int $seat_id
 * @property int $order_id
 * @property int $user_id
 * @property string $status 'HELD', 'RELEASED', 'CONVERTED'
 * @property string $held_at
 * @property string $expires_at
 */
class SeatHold extends Model
{
    protected string $table = 'seat_holds';

    protected array $fillable = [
        'showtime_id',
        'seat_id',
        'order_id',
        'user_id',
        'status',
        'held_at',
        'expires_at',
    ];
}
