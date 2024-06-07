<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;

class PaymentRefundService
{
    public const EXCEPTION_MSG = 'Unable to refund order #%1 on TrueLayer';

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;
    /**
     * @var TransactionRepository
     */
    private TransactionRepository $transactionRepository;

    /**
     * RefundOrder constructor.
     *
     * @param ClientFactory $clientFactory
     * @param TransactionRepository $transactionRepository
     */
    public function __construct(
        ClientFactory $clientFactory,
        TransactionRepository $transactionRepository
    ) {
        $this->clientFactory = $clientFactory;
        $this->transactionRepository = $transactionRepository;
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
     */
    public function execute(OrderInterface $order, float $amount): ?string
    {
        $transaction = $this->transactionRepository->getByOrderId((int) $order->getEntityId());

        if ($amount == 0) {
            return null;
        }

        $client = $this->clientFactory->create((int) $order->getStoreId());

        try {
            $refundId = $client->refund()
                ->payment($transaction->getPaymentUuid())
                ->amountInMinor((int)bcmul((string)$amount, '100'))
                ->create()
                ->getId();
        } catch (\Exception $e) {
            throw new LocalizedException(__(self::EXCEPTION_MSG, $order->getIncrementId()));
        }

        if (!$refundId) {
            throw new LocalizedException(__(self::EXCEPTION_MSG, $order->getIncrementId()));
        }

        return $refundId;
    }
}
