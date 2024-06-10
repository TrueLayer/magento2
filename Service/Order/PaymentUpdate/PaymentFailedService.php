<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\PaymentUpdate;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Service\Order\PaymentFailureReasonService;
use TrueLayer\Connect\Api\Transaction\Data\DataInterface as TransactionInterface;

class PaymentFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private PaymentFailureReasonService $paymentFailureReasonService;
    private TransactionService $transactionService;
    private LogService $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentFailureReasonService $paymentFailureReasonService
     * @param TransactionService $transactionService
     * @param LogService $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentFailureReasonService $paymentFailureReasonService,
        TransactionService $transactionService,
        LogService $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentFailureReasonService = $paymentFailureReasonService;
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentId
     * @param string $failureReason
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function handle(string $paymentId, string $failureReason): void
    {
        $this->transactionService
            ->logger($this->logger->prefix("PaymentFailedService $paymentId"))
            ->paymentId($paymentId)
            ->execute(fn($transaction) => $this->cancelOrder($transaction, $failureReason));
    }

    /**
     * @param TransactionInterface $transaction
     * @param string $failureReason
     */
    private function cancelOrder(TransactionInterface $transaction, string $failureReason): void
    {
        $order = $this->orderRepository->get($transaction->getOrderId());

        if (!$order->isCanceled()) {
            $order->cancel();
        }

        $orderComment = "Order cancelled. {$this->paymentFailureReasonService->getHumanReadableLabel($failureReason)} ($failureReason)";
        $order->addStatusToHistory($order->getStatus(), $orderComment, true);
        $this->orderRepository->save($order);

        $transaction->setStatus('payment_failed');
        $transaction->setFailureReason($failureReason);
    }
}
