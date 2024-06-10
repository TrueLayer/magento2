<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService;


class CreditmemoObserver implements ObserverInterface
{

    public function execute(Observer $observer)
    {
//        /** @var Creditmemo $creditmemo */
//        $creditmemo = $observer->getData('creditmemo');
//
//        $order = $creditmemo->getOrder();
//
//        /** @var Order\Payment $payment */
//        $payment = $order->getPayment();

    }
}