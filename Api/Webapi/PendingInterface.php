<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Webapi;

/**
 * Check if order is placed by transaction token
 * @api
 */
interface PendingInterface
{
    /**
     * @param string $token
     *
     * @return bool
     */
    public function checkOrderPlaced(string $token): bool;
}
