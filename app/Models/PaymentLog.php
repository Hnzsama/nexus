<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class PaymentLog
 * 
 * Immutable audit trail untuk setiap payload notifikasi / webhook yang diterima.
 * 
 * @property int $id
 * @property string $order_number
 * @property string $event_type
 * @property string|null $status_code
 * @property string $raw_payload
 * @property string|null $ip_address
 * @property int $signature_valid
 * @property string $created_at
 */
class PaymentLog extends Model
{
    protected string $table = 'payment_logs';

    protected array $fillable = [
        'order_number',
        'event_type',
        'status_code',
        'raw_payload',
        'ip_address',
        'signature_valid',
    ];
}
