<?php

namespace App\Enum;

enum MenuStatusEnum : int
{
    case active = 5;
    case inactive = 10;
    case pulled_out = 15;
    case out_of_stock = 20;
}
