<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property float $amount
 * @property float $quantity
 * @property string $unit
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
 *
 * @mixin \Eloquent
 */
class Supply extends Model
{
    protected $fillable = [
        'name', 'slug', 'amount', 'quantity', 'unit',
    ];
}
