<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Transaction;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;

/**
 * Class GenerateToken
 */
class GenerateToken
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var Random
     */
    private $mathRandom;

    /**
     * GenerateToken constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param Random $mathRandom
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        Random $mathRandom
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->mathRandom = $mathRandom;
    }

    /**
     * @param int $quoteId
     *
     * @return string|null
     *
     * @throws LocalizedException
     */
    public function execute(int $quoteId): ?string
    {
        try {
            $transaction = $this->transactionRepository->getByQuoteId($quoteId, true);
            return $transaction->getToken();
        } catch (\Exception $exception) {
            $token = $this->mathRandom->getUniqueHash('trl');
            $transaction = $this->transactionRepository->create();
            $transaction->setQuoteId($quoteId)->setToken($token);
            $this->transactionRepository->save($transaction);
            return $token;
        }
    }
}
