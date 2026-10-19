<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Ticket
 * 
 * Tiket resmi bioskop pasca pelunasan transaksi (Settlement).
 * 
 * @property int $id
 * @property int $order_item_id
 * @property string $ticket_code
 * @property string $qr_token
 * @property string $checkin_status 'UNCLAIMED', 'CHECKED_IN'
 * @property string|null $checked_in_at
 * @property string $created_at
 */
class Ticket extends Model
{
    protected string $table = 'tickets';

    protected array $fillable = [
        'order_item_id',
        'ticket_code',
        'qr_token',
        'checkin_status',
        'checked_in_at',
    ];
}
