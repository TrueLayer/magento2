<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Webapi;

/**
 * Checkout order request interface
 * @api
 */
interface CheckoutInterface
{
    /**
     * @param bool $isLoggedIn
     * @param string $cartId
     * @return mixed
     */
    public function orderRequest(bool $isLoggedIn, string $cartId);
}
