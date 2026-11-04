<?php

namespace App\Enum;

enum UserRoleEnum: int
{
    case cashier = 5;
    case admin = 10;
    case no_access = 100;
}
