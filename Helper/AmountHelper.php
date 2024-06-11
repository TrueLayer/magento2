<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Helper;

class AmountHelper
{
    public static function toMinor($amount): int
    {
        return (int) round($amount * 100, 0, PHP_ROUND_HALF_UP);
    }
}