<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\Webapi\PendingInterface;

class Pending implements PendingInterface
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * Pending constructor.
     *
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository
    ) {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @inheritDoc
     */
    public function checkOrderPlaced(string $token): bool
    {
        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($token);
            return (bool)$transaction->getOrderId();
        } catch (InputException|NoSuchEntityException $e) {
            return false;
        }
    }
}
