<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class Payment
 * 
 * @property int $id
 * @property int $order_id
 * @property string|null $snap_token
 * @property string|null $payment_type
 * @property string $transaction_status
 * @property string|null $fraud_status
 * @property string|null $midtrans_transaction_id
 * @property float $gross_amount
 * @property string|null $settlement_time
 * @property string|null $raw_response_json
 * @property string $created_at
 * @property string $updated_at
 */
class Payment extends Model
{
    protected string $table = 'payments';

    protected array $fillable = [
        'order_id',
        'snap_token',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'midtrans_transaction_id',
        'gross_amount',
        'settlement_time',
        'raw_response_json',
    ];
}
