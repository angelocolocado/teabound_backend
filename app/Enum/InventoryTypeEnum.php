<?php

namespace App\Enum;

enum InventoryTypeEnum: int
{
    case ingredients = 5;
    case supplies = 10;
    case products = 15;
}
