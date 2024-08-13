<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Quote\Api\Data\CartInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepositoryInterface;


class OrderTotalCheck extends AbstractCheck
{
    private ConfigRepositoryInterface $configRepository;

    public function __construct(ConfigRepositoryInterface $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function isAllowed(CartInterface $quote): bool
    {
        $min = $this->configRepository->getMinimumOrderTotal();
        $max = $this->configRepository->getMaximumOrderTotal();

        if ($min && ($min >= $quote->getGrandTotal())) {
            return false;
        }

        if ($max && ($max <= $quote->getGrandTotal())) {
            return false;
        }

        return true;
    }
}