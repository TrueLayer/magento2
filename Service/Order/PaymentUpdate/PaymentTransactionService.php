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
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;
use TrueLayer\Connect\Service\Order\BaseTransactionService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\Exceptions\OrderNotReadyException;

class PaymentTransactionService extends BaseTransactionService
{
    private PaymentTransactionRepositoryInterface $transactionRepository;
    private string $paymentId;

    /**
     * @param PaymentTransactionRepositoryInterface $transactionRepository
     * @param LogServiceInterface $logger
     */
    public function __construct(PaymentTransactionRepositoryInterface $transactionRepository, LogServiceInterface $logger)
    {
        $this->transactionRepository = $transactionRepository;
        parent::__construct($logger);
    }

    /**
     * @param string $paymentId
     * @return $this
     */
    public function paymentId(string $paymentId): self
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @return BaseTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException|OrderNotReadyException
     */
    protected function getTransaction(): BaseTransactionDataInterface
    {
        $transaction = $this->transactionRepository->getByPaymentUuid($this->paymentId);

        if (!$transaction->getOrderId()) {
           throw new OrderNotReadyException();
        }

        return $transaction;
    }

    /**
     * @param BaseTransactionDataInterface $transaction
     * @throws LocalizedException
     */
    protected function saveTransaction(BaseTransactionDataInterface $transaction): void
    {
        $this->transactionRepository->save($transaction);
    }
}
