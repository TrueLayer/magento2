<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Helper;

class ValidationHelper
{
    /**
     * @param mixed $input
     * @return bool
     */
    public static function isUUID($input): bool
    {
        if (!is_string($input)) {
            return false;
        }

        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $input) === 1;
    }
}
