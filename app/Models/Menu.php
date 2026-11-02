<?php

namespace App\Models;

use App\Enum\MenuStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['category_id', 'name', 'notes', 'status'];

    protected $casts = [
        'status' => MenuStatusEnum::class,
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function menuContents(): HasMany
    {
        return $this->hasMany(MenuContent::class, 'menu_id');
    }

    public function menuOptions(): HasMany
    {
        return $this->hasMany(MenuOption::class, 'menu_id');
    }
}
