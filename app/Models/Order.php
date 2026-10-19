<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Order
 * 
 * @property int $id
 * @property string $order_number
 * @property int $user_id
 * @property int $showtime_id
 * @property int $total_seats
 * @property float $total_amount
 * @property string $status
 * @property string $expires_at
 * @property string|null $paid_at
 * @property string|null $cancelled_at
 * @property string $created_at
 * @property string $updated_at
 */
class Order extends Model
{
    protected string $table = 'orders';

    protected array $fillable = [
        'order_number',
        'user_id',
        'showtime_id',
        'total_seats',
        'total_amount',
        'status',
        'expires_at',
        'paid_at',
        'cancelled_at',
    ];
}
