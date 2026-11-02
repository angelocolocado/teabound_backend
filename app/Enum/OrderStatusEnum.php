<?php

namespace App\Enum;

enum OrderStatusEnum: string
{
    case preparing = 'preparing';
    case ready = 'ready';
    case claimed = 'claimed';
    case voided = 'voided';
}
