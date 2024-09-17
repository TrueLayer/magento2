<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\PaymentUpdate;

use Exception;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Helper\PaymentFailureReasonHelper;

class PaymentFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private OrderPaymentRepositoryInterface $paymentRepository;
    private PaymentTransactionService $transactionService;
    private LogServiceInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPaymentRepositoryInterface $paymentRepository
     * @param PaymentTransactionService $transactionService
     * @param LogServiceInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderPaymentRepositoryInterface $paymentRepository,
        PaymentTransactionService $transactionService,
        LogServiceInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentRepository = $paymentRepository;
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentId
     * @param string $failureReason
     * @throws Exception
     */
    public function handle(string $paymentId, string $failureReason): void
    {
        $prefix = "PaymentFailedService $paymentId";
        $this->logger->addPrefix($prefix);

        $this->transactionService
            ->paymentId($paymentId)
            ->execute(fn($transaction) => $this->cancelOrder($transaction, $failureReason));

        $this->logger->removePrefix($prefix);
    }

    /**
     * @param PaymentTransactionDataInterface $transaction
     * @param string $failureReason
     */
    private function cancelOrder(PaymentTransactionDataInterface $transaction, string $failureReason): void
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($transaction->getOrderId());

        if (!$order->isCanceled()) {
            $order->cancel();
            $this->logger->debug('Order cancelled');
        }

        // Update order payment
        $payment = $order->getPayment();
        $payment->setLastTransId($transaction->getPaymentUuid());
        $payment->cancel();
        $payment->setIsTransactionClosed(true);
        $this->paymentRepository->save($payment);

        $niceMessage = PaymentFailureReasonHelper::getHumanReadableLabel($failureReason);
        $orderComment = "Order cancelled. $niceMessage ($failureReason)";
        $order->addStatusToHistory($order->getStatus(), $orderComment, true);
        $this->orderRepository->save($order);
        $this->logger->debug('Order comment added');

        $transaction->setPaymentFailed();
        $transaction->setFailureReason($failureReason);
        $this->logger->debug('Payment transaction updated');
    }
}
