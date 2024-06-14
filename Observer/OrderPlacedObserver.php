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
use TrueLayer\Connect\Api\Log\LogServiceInterface;

class OrderPlacedObserver implements ObserverInterface
{
    private OrderRepositoryInterface $orderRepository;
    private LogServiceInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogServiceInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LogServiceInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger->addPrefix('OrderPlacedObserver');
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if ($order->getPayment()->getMethod() !== 'truelayer') {
            return;
        }

        $this->logger->debug('Start');

        // Set order status to pending payment
        $order
            ->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(Order::STATE_PENDING_PAYMENT);

        $this->orderRepository->save($order);
        $this->logger->debug('End');
    }
}
