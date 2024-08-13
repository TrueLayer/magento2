<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Quote\Api\Data\CartInterface;

abstract class AbstractCheck
{
    public abstract function isAllowed(CartInterface $quote): bool;
}