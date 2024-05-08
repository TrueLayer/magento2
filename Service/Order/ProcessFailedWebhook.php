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
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;

/**
 * Class ProcessSettledWebhook
 */
class ProcessFailedWebhook
{
    private TransactionRepository $transactionRepository;

    private OrderRepositoryInterface $orderRepository;

    private LogRepository $logRepository;

    private OrderInterface $orderInterface;

    /**
     * ProcessFailedWebhook constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logRepository
     * @param OrderInterface $orderInterface
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        LogRepository $logRepository,
        OrderInterface $orderInterface
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->logRepository = $logRepository;
        $this->orderInterface = $orderInterface;
    }

    /**
     * Place order via webhook
     *
     * @param string $uuid
     * @param string $userId
     */
    public function execute(string $uuid)
    {
        $this->logRepository->addDebugLog('webhook - failed payment - start processing ', $uuid);

        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($uuid);

            $this->logRepository->addDebugLog('webhook', [
                'transaction id' => $transaction->getEntityId(),
                'quote id' => $transaction->getQuoteId(),
            ]);

            if (!$transaction->getQuoteId()) {
                $this->logRepository->addDebugLog('webhook', 'aborting, no quote found');
                return;
            }

            if ($transaction->getIsLocked()) {
                $this->logRepository->addDebugLog('webhook', 'aborting, transaction is locked');
                return;
            }

            $this->logRepository->addDebugLog('webhook', 'locking transaction and starting order deletion');
            $this->transactionRepository->lock($transaction);
            $order = $this->orderInterface->loadByAttribute('quote_id', $transaction->getQuoteId());
            $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
            $this->orderRepository->save($order);

            // Update transaction status
            $transaction->setStatus('payment_failed');
            $this->transactionRepository->unlock($transaction);
            $this->logRepository->addDebugLog('webhook', 'transaction status set to failed');
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('webhook - exception', $e->getMessage());
            throw $e;
        }
    }
}
