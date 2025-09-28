<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $amount
 * @property float $paid_amount
 * @property float $quantity
 * @property string $unit
 * @property string|null $description
 * @property int|null $customer_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supply whereCustomerId($value)
 *
 * @mixin \Eloquent
 */
class Supply extends Model
{
    protected $fillable = [
        'name', 'slug', 'amount', 'quantity', 'unit', 'description', 'customer_id', 'paid_amount',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
