<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\RefundUpdate;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionRepositoryInterface;
use TrueLayer\Connect\Service\Order\BaseTransactionService;

class RefundTransactionService extends BaseTransactionService
{
    private RefundTransactionRepositoryInterface $transactionRepository;
    private string $refundId;

    /**
     * @param RefundTransactionRepositoryInterface $transactionRepository
     * @param LogService $logger
     */
    public function __construct(RefundTransactionRepositoryInterface $transactionRepository, LogService $logger)
    {
        $this->transactionRepository = $transactionRepository;
        parent::__construct($logger);
    }

    /**
     * @param string $refundId
     * @return $this
     */
    public function refundId(string $refundId): self
    {
        $this->refundId = $refundId;
        return $this;
    }

    /**
     * @return BaseTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    protected function getTransaction(): BaseTransactionDataInterface
    {
        return $this->transactionRepository->getByRefundUuid($this->refundId);
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
