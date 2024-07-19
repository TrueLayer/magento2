<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;

class OrderPlacedObserver implements ObserverInterface
{
    private OrderRepositoryInterface $orderRepository;
    private PaymentTransactionRepositoryInterface $paymentTransactionRepository;
    private Session $session;
    private LogServiceInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentTransactionRepositoryInterface $paymentTransactionRepository
     * @param Session $session
     * @param LogServiceInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentTransactionRepositoryInterface $paymentTransactionRepository,
        Session $session,
        LogServiceInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->session = $session;
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

        // Restore the quote so users can check out again if they come back
        // The order success page will clear the quote by default
        $this->session->restoreQuote();

        $this->handleCheckoutWidgetPayment($order);

        $this->logger->debug('End');
    }

    private function handleCheckoutWidgetPayment(OrderInterface $order)
    {
        // Check to see if we have a transaction entry for the quote with no order id set
        // This would exist for checkout widget payments, where the order is placed after payment creation
        $transaction = $this->paymentTransactionRepository->getOneByColumns([
            PaymentTransactionDataInterface::QUOTE_ID => (int) $order->getQuoteId(),
            PaymentTransactionDataInterface::ORDER_ID => ['null' => true],
        ], [PaymentTransactionDataInterface::ENTITY_ID => 'DESC']);

        if (!$transaction) {
            return;
        }

        // Link the order id to the transaction
        $transaction->setOrderId((int)$order->getEntityId());
        $this->paymentTransactionRepository->save($transaction);
    }
}
