<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Seat
 * 
 * Master representasi fisik kursi.
 * PERINGATAN INTEGRITAS: Dilarang keras menaruh status LOCKED/BOOKED langsung pada tabel master ini.
 * Status dihitung secara dinamis berbasis showtime_id, seat_holds, dan order_items.
 * 
 * @property int $id
 * @property int $studio_id
 * @property string $row_label
 * @property int $seat_number
 * @property string $seat_type
 * @property int $is_active
 * @property string $created_at
 */
class Seat extends Model
{
    protected string $table = 'seats';

    protected array $fillable = [
        'studio_id',
        'row_label',
        'seat_number',
        'seat_type',
        'is_active',
    ];
}
