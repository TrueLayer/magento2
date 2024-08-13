<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Quote\Api\Data\CartInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepositoryInterface;


class CurrencyCheck extends AbstractCheck
{
    private ConfigRepositoryInterface $configRepository;

    public function __construct(ConfigRepositoryInterface $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function isAllowed(CartInterface $quote): bool
    {
        $allowedCurrencies = $this->configRepository->isCheckoutWidgetEnabled()
            ? ['GBP']
            : $this->configRepository->getCurrencies($quote->getStoreId());

        return in_array($quote->getBaseCurrencyCode(), $allowedCurrencies);
    }
}