<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;

abstract class BaseTransactionService
{
    private LogServiceInterface $logger;

    /**
     * @param LogServiceInterface $logger
     */
    public function __construct(LogServiceInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param callable $fn
     * @throws Exception
     */
    public function execute(callable $fn): void
    {
        $this->logger->addPrefix('Transaction')->debug('Start');

        $transaction = $this->getTransaction();
        $this->validateTransaction($transaction);

        if ($transaction->getIsLocked()) {
            $this->logger->debug('Aborting, locked');
            return;
        }

        if (!in_array($transaction->getStatus(), ['NULL', null])) {
            $this->logger->debug('Aborting, already completed');
            return;
        }

        $this->logger->debug('Locking');
        $transaction->setIsLocked(true);
        $this->saveTransaction($transaction);

        try {
            $this->logger->debug('Execute logic');
            $fn($transaction);
        } catch (Exception $e) {
            $this->logger->error('Exception in transaction', $e);
            throw $e;
        } finally {
            $transaction->setIsLocked(false);
            $this->saveTransaction($transaction);
            $this->logger->debug('Unlocked transaction');
            $this->logger->removePrefix('Transaction');
        }
    }

    /**
     * @return BaseTransactionDataInterface
     */
    abstract protected function getTransaction(): BaseTransactionDataInterface;

    /**
     * @param BaseTransactionDataInterface $transaction
     */
    abstract protected function saveTransaction(BaseTransactionDataInterface $transaction): void;

    /**
     * @throws Exception
     */
    private function validateTransaction(BaseTransactionDataInterface $transaction): void
    {
        $this->logger->debug("Details", [
            'transaction id' => $transaction->getEntityId(),
            'order id' => $transaction->getOrderId(),
        ]);

        if (!$transaction->getOrderId()) {
            $this->logger->error('Transaction with missing order found');
            throw new Exception('Transaction with missing order found');
        }
    }
}
