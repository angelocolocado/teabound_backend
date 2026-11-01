<?php

namespace App\Models;

use App\Enum\InventoryTypeEnum;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = ['name', 'type', 'stock_in_pcs'];

    protected $casts = [
        'type' => InventoryTypeEnum::class,
    ];
}
