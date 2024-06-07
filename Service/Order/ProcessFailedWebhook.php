<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;

/**
 * Class ProcessSettledWebhook
 */
class ProcessFailedWebhook
{
    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var PaymentFailureReasonService
     */
    private PaymentFailureReasonService $paymentFailureReasonService;

    /**
     * @var TransactionRepository
     */
    private TransactionRepository $transactionRepository;

    /**
     * @var LogRepository
     */
    private LogRepository $logRepository;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentFailureReasonService $paymentFailureReasonService
     * @param TransactionRepository $transactionRepository
     * @param LogRepository $logRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PaymentFailureReasonService $paymentFailureReasonService,
        TransactionRepository $transactionRepository,
        LogRepository $logRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentFailureReasonService = $paymentFailureReasonService;
        $this->transactionRepository = $transactionRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * @param string $paymentId
     * @param string $failureReason
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $paymentId, string $failureReason)
    {
        $this->logRepository->debug('webhook - failed payment - start processing ', $paymentId);

        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);

            $this->logRepository->debug('webhook', [
                'transaction id' => $transaction->getEntityId(),
                'order id' => $transaction->getOrderId(),
            ]);

            if (!$transaction->getOrderId()) {
                $this->logRepository->debug('webhook', 'aborting, no order found');
                return;
            }

            if ($transaction->getIsLocked()) {
                $this->logRepository->debug('webhook', 'aborting, transaction is locked');
                return;
            }

            $this->logRepository->debug('webhook', 'locking transaction and starting order deletion');
            $this->transactionRepository->lock($transaction);
            $order = $this->orderRepository->get($transaction->getOrderId());
            $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);

            $orderComment = "{$this->paymentFailureReasonService->getHumanReadableLabel($failureReason)} ({$failureReason})";
            $order->addStatusToHistory($order->getStatus(), $orderComment, true);

            $this->orderRepository->save($order);

            // Update transaction status
            $transaction->setStatus('payment_failed');
            $this->transactionRepository->unlock($transaction);
            $this->logRepository->debug('webhook', 'transaction status set to failed');
        } catch (Exception $e) {
            $this->logRepository->debug('webhook - exception', $e->getMessage());
            throw $e;
        }
    }
}
