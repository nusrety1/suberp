<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Purchase
 *
 * @property int $id
 * @property int $customer_id
 * @property string $repayment_date
 * @property float $bargain_price
 * @property-read Customer $customer
 * @property-read PurchaseProduct $purchaseProducts
 *
 * @method static where(string $column, mixed $value) : Builder
 * @method static create(array $attributes) : Model
 *
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $description
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereBargainPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereRepaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Purchase extends Model
{
    protected $fillable = [
        'customer_id', 'repayment_date', 'bargain_price',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(PurchaseProduct::class);
    }
}
