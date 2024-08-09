<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\StoreInterface;
use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;

abstract class AbstractCheck
{
    public abstract function isAllowed(StoreInterface $store): bool;
}