<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuContent extends Model
{
    protected $fillable = ['menu_id', 'inventory_id', 'name', 'stock_to_deduct_in_pcs'];

    public function inventory() : BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

}
