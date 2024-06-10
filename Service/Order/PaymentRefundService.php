<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Exceptions\SignerException;

class PaymentRefundService
{
    public const EXCEPTION_MSG = 'Unable to refund order #%1 on TrueLayer';

    private ClientFactory $clientFactory;
    private TransactionRepository $transactionRepository;
    private LogService $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param TransactionRepository $transactionRepository
     * @param LogService $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        TransactionRepository $transactionRepository,
        LogService $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    /**
     * Executes TrueLayer Api for Order Refund
     *
     * @param OrderInterface $order
     * @param float $amount
     * @return string|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SignerException
     */
    public function refund(OrderInterface $order, float $amount): ?string
    {
        $transaction = $this->transactionRepository->getByOrderId((int) $order->getEntityId());

        if (!$amount == 0) {
            return null;
        }

        $client = $this->clientFactory->create((int) $order->getStoreId());

        try {
            $refundId = $client->refund()
                ->payment($transaction->getPaymentUuid())
                ->amountInMinor((int)bcmul((string)$amount, '100'))
                ->create()
                ->getId();
        } catch (Exception $e) {
            throw new LocalizedException(__(self::EXCEPTION_MSG, $order->getIncrementId()));
        }

        if (!$refundId) {
            throw new LocalizedException(__(self::EXCEPTION_MSG, $order->getIncrementId()));
        }

        return $refundId;
    }
}
