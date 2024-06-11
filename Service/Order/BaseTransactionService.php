<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;

abstract class BaseTransactionService
{
    private LogService $logger;

    /**
     * @param LogService $logger
     * @return $this
     */
    public function logger(LogService $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param callable $fn
     * @throws Exception
     */
    public function execute(Callable $fn): void
    {
        $transaction = $this->getTransaction();
        $this->validateTransaction($transaction);

        if ($transaction->getIsLocked()) {
            $this->logger->debug('Aborting, locked transaction');
            return;
        }

        if (!in_array($transaction->getStatus(), ['NULL', null])) {
            $this->logger->debug('Aborting, transaction already completed');
            return;
        }

        $this->logger->debug('Locking transaction');
        $transaction->setIsLocked(true);
        $this->saveTransaction($transaction);

        try {
            $this->logger->debug('Execute logic');
            $fn($transaction);
        } catch (\Exception $e) {
            $this->logger->error('Exception in transaction', $e);
            throw $e;
        } finally {
            $transaction->setIsLocked(false);
            $this->saveTransaction($transaction);
            $this->logger->debug('Unlocked transaction');
        }
    }

    /**
     * @return BaseTransactionDataInterface
     */
    protected abstract function getTransaction(): BaseTransactionDataInterface;

    /**
     * @param BaseTransactionDataInterface $transaction
     */
    protected abstract function saveTransaction(BaseTransactionDataInterface $transaction): void;

    /**
     * @throws Exception
     */
    private function validateTransaction(BaseTransactionDataInterface $transaction): void
    {
        $this->logger->debug("Transaction", [
            'transaction id' => $transaction->getEntityId(),
            'order id' => $transaction->getOrderId(),
        ]);

        if (!$transaction->getOrderId()) {
            throw new Exception('Transaction with missing order found');
        }
    }
}
