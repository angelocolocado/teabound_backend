<?php

namespace App\Models;

use App\Enum\OrderStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'status',
        'subtotal',
        'discount_amount',
        'total',
        'coupon_id',
        'coupon_code',
        'void_reason',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class,
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function paymentRecord(): HasOne
    {
        return $this->hasOne(PaymentRecord::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopeNotVoided(Builder $query): Builder
    {
        return $query->where('status', '!=', OrderStatusEnum::voided->value);
    }
}
