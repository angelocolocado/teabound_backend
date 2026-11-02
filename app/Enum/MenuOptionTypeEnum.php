<?php

namespace App\Enum;

enum MenuOptionTypeEnum: string
{
    case Size = 'size';
    case Topping = 'topping';
    case Combo = 'combo';
    case Addon = 'addon';
}
