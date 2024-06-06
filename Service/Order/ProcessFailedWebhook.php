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
    private TransactionRepository $transactionRepository;
    private OrderRepositoryInterface $orderRepository;
    private LogRepository $logRepository;

    /**
     * ProcessFailedWebhook constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        LogRepository $logRepository,
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
    }

    /**
     * Place order via webhook
     *
     * @param string $uuid
     * @param string $userId
     */
    public function execute(string $uuid)
    {
        $this->logRepository->debug('webhook - failed payment - start processing ', $uuid);

        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($uuid);

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
