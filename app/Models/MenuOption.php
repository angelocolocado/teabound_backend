<?php

namespace App\Models;

use App\Enum\MenuOptionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuOption extends Model
{
    protected $fillable = [
        'menu_id',
        'name',
        'type',
        'price',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'type' => MenuOptionTypeEnum::class,
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function menuContents(): HasMany
    {
        return $this->hasMany(MenuContent::class, 'menu_option_id');
    }
}
