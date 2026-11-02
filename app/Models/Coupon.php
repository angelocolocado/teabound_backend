<?php

namespace App\Models;

use App\Enum\DiscountTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'minimum_purchase',
        'discount_cap',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'type' => DiscountTypeEnum::class,
        'value' => 'decimal:2',
        'minimum_purchase' => 'decimal:2',
        'discount_cap' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
