<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\AbstractCheck;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\CountryCheck;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\CurrencyCheck;

class AvailabilityService
{
    private StoreManagerInterface $storeManager;

    /**
     * @var AbstractCheck[]
     */
    private array $checks;

    public function __construct(
        StoreManagerInterface $storeManager,
        CountryCheck $countryCheck,
        CurrencyCheck $currencyCheck
    )
    {
        $this->storeManager = $storeManager;
        $this->checks = [ $countryCheck, $currencyCheck ];
    }

    public function isAvailable(CartInterface $quote): bool
    {
        $store = $this->storeManager->getStore();

        foreach ($this->checks as $check) {
            if (!$check->isAllowed($store, $quote)) {
                return false;
            }
        }

        return true;
    }
}