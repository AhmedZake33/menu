<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case RestaurantAdmin = 'restaurant_admin';
}
