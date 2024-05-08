<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;


class OrderPlacedHandler implements ObserverInterface
{
    private OrderRepositoryInterface $orderRepository;
    private LogRepository $logRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LogRepository $logRepository,
    ) {
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethod() !== 'truelayer') {
            return;
        }

        $order
            ->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(Order::STATE_PENDING_PAYMENT);

        $this->logRepository->addDebugLog('Order created', 'set to pending payment');

        $this->orderRepository->save($order);
    }
}