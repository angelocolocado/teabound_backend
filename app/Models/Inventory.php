<?php

namespace App\Models;

use App\Enum\InventoryTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['name', 'type', 'stock_in_pcs', 'low_stock_threshold', 'display_unit'];

    protected $casts = [
        'type' => InventoryTypeEnum::class,
    ];
}
