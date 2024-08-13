<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod;

use Magento\Quote\Api\Data\CartInterface;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\AbstractCheck;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\CountryCheck;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\CurrencyCheck;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks\OrderTotalCheck;

class PaymentMethodService
{
    /**
     * @var AbstractCheck[]
     */
    private array $checks;

    public function __construct(
        CountryCheck $countryCheck,
        CurrencyCheck $currencyCheck,
        OrderTotalCheck $orderTotalCheck
    )
    {
        $this->checks = [ $countryCheck, $currencyCheck, $orderTotalCheck ];
    }

    public function isAvailable(CartInterface $quote): bool
    {
        foreach ($this->checks as $check) {
            if (!$check->isAllowed($quote)) {
                return false;
            }
        }

        return true;
    }
}