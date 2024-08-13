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
use TrueLayer\Connect\Service\PaymentMethod\PaymentMethodService;

class PaymentMethodIsActiveObserver implements ObserverInterface
{
    private PaymentMethodService $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
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
        $observer->getEvent()->getResult()->setData('is_available', $this->paymentMethodService->isAvailable($quote));
    }
}