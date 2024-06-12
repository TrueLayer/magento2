<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\PaymentUpdate;

use Exception;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Helper\PaymentFailureReasonHelper;

class PaymentFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private PaymentTransactionService $transactionService;
    private LogService $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentTransactionService $transactionService
     * @param LogService $logger
     */
    public function __construct(
        OrderRepositoryInterface    $orderRepository,
        PaymentTransactionService  $transactionService,
        LogService                  $logger
    ) {
        $this->orderRepository = $orderRepository;
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
        $order = $this->orderRepository->get($transaction->getOrderId());

        if (!$order->isCanceled()) {
            $order->cancel();
            $this->logger->debug('Order cancelled');
        }

        $niceMessage = PaymentFailureReasonHelper::getHumanReadableLabel($failureReason);
        $orderComment = "Order cancelled. {$niceMessage} ($failureReason)";
        $order->addStatusToHistory($order->getStatus(), $orderComment, true);
        $this->orderRepository->save($order);
        $this->logger->debug('Order comment added');

        $transaction->setPaymentFailed();
        $transaction->setFailureReason($failureReason);
        $this->logger->debug('Payment transaction updated');
    }
}
