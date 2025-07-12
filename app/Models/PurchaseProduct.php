<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Purchase
 *
 * @property int $id
 * @property int $customer_id
 * @property string $repayment_date
 * @property float $bargain_price
 * @property-read Customer $customer
 *
 * @method static where(string $column, mixed $value) : Builder
 * @method static create(array $attributes) : Model
 *
 * @property int $purchase_id
 * @property int $product_id
 * @property int $quantity
 * @property float $purchase_time_unit_price
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct wherePurchaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct wherePurchaseTimeUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseProduct whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PurchaseProduct extends Model
{
    protected $fillable = [
        'purchase_id', 'product_id', 'quantity', 'purchase_time_unit_price',
    ];
}
