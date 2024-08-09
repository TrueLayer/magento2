<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentMethodInterface;
use TrueLayer\Connect\Service\PaymentMethod\AvailabilityService;

class PaymentMethodIsActiveObserver implements ObserverInterface
{
    private AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function execute(Observer $observer)
    {
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = $observer->getEvent()->getMethodInstance();

        if ($paymentMethod->getCode() != 'truelayer') {
            return;
        }

        /** @var CartInterface $quote */
        $quote = $observer->getEvent()->getQuote();

        if (!$this->availabilityService->isAvailable($quote)) {
            $observer->getEvent()->getResult()->setData('is_available', false);
        }
    }
}
