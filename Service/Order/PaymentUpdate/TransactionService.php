<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\PaymentUpdate;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Data\DataInterface as TransactionInterface;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;

class TransactionService
{
    private TransactionRepository $transactionRepository;
    private LogService $logger;
    private TransactionInterface $transaction;

    /**
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(TransactionRepository $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

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
     * @param string $paymentId
     * @return $this
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function paymentId(string $paymentId): self
    {
        $this->setTransaction($paymentId);
        return $this;
    }

    /**
     * @return TransactionInterface
     */
    public function getTransaction(): TransactionInterface
    {
        return $this->transaction;
    }

    /**
     * @param callable $fn
     * @throws LocalizedException
     */
    public function execute(Callable $fn): void
    {
        if ($this->transaction->getIsLocked()) {
            $this->logger->debug('Aborting, locked transaction');
            return;
        }

        if ($this->transaction->getStatus() !== 'NULL') {
            $this->logger->debug('Aborting, transaction already completed');
            return;
        }

        $this->logger->debug('Locking transaction');
        $this->transactionRepository->lock($this->transaction);

        $this->logger->debug('Execute logic');
        $fn($this->transaction);

        $this->transactionRepository->unlock($this->transaction);
        $this->transactionRepository->save($this->transaction);
        $this->logger->debug('Unlocked transaction');
    }

    /**
     * @param string $paymentId
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function setTransaction(string $paymentId): void
    {
        $this->transaction = $this->transactionRepository->getByPaymentUuid($paymentId);

        $this->logger->debug("Found transaction", [
            'transaction id' => $this->transaction->getEntityId(),
            'order id' => $this->transaction->getOrderId(),
        ]);

        if (!$this->transaction->getOrderId()) {
            throw new Exception('Transaction with missing order found');
        }
    }
}
