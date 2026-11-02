<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuContent extends Model
{
    protected $fillable = ['menu_option_id', 'inventory_id', 'name', 'stock_to_deduct_in_pcs'];

    protected $casts = [
        'stock_to_deduct_in_pcs' => 'integer',
    ];

    public function menuOption(): BelongsTo
    {
        return $this->belongsTo(MenuOption::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
