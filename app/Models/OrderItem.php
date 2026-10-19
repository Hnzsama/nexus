<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class OrderItem
 * 
 * @property int $id
 * @property int $order_id
 * @property int $seat_id
 * @property float $price
 * @property string $created_at
 */
class OrderItem extends Model
{
    protected string $table = 'order_items';

    protected array $fillable = [
        'order_id',
        'seat_id',
        'price',
    ];
}
