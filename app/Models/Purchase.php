<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Purchase
 * @property int $customer_id
 * @property string $repayment_date
 * @property float $bargain_price
 *
 * @property-read Customer $customer
 * @method static where(string $column, mixed $value) : Builder
 * @method static create(array $attributes) : Model
 *
 * @package App
 */

class Purchase extends Model
{
    protected $fillable = [
        'customer_id', 'repayment_date', 'bargain_price'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
