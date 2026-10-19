<?php

declare(strict_types=1);

namespace App\Models;

use Core\Database\Model;

/**
 * Class PricingTier
 * 
 * @property int $id
 * @property string $code
 * @property string $name
 * @property float $multiplier
 * @property float $surcharge
 * @property string|null $description
 * @property int $is_active
 * @property string $created_at
 * @property string $updated_at
 */
class PricingTier extends Model
{
    protected string $table = 'pricing_tiers';

    protected array $fillable = [
        'code',
        'name',
        'multiplier',
        'surcharge',
        'description',
        'is_active',
    ];
}
