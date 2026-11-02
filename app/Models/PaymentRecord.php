<?php

namespace App\Models;

use App\Enum\PaymentStatusEnum;
use App\Enum\PaymentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRecord extends Model
{
    protected $fillable = [
        'order_id',
        'payment_method',
        'status',
        'gcash_customer_number',
        'gcash_reference_number',
        'amount',
        'recorded_by',
    ];

    protected $casts = [
        'payment_method' => PaymentTypeEnum::class,
        'status' => PaymentStatusEnum::class,
        'amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
