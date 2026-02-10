<?php

namespace App\Domain\Enums;

enum PriceAdjustmentType: string
{
    case PERCENTAGE = 'price_percentage';
    case FIXED = 'price_fixed';
}
