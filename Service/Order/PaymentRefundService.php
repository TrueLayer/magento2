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
use TrueLayer\Connect\Service\Api\ClientFactory;

class PaymentRefundService
{

    public const EXCEPTION_MSG = 'Unable to refund order #%1 on TrueLayer';

    /**
     * @var ClientFactory
     */
    private $clientFactory;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

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
     * @return array
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\ValidationException
     */
    public function execute(OrderInterface $order, float $amount): array
    {
        $transaction = $this->transactionRepository->getByOrderId((int)$order->getId());

        if ($amount != 0) {
            $client = $this->clientFactory->create((int)$order->getStoreId());
            $refundId = $client->refund()
                ->payment($transaction->getPaymentUuid())
                ->amountInMinor((int)bcmul((string)$amount, '100'))
                ->reference($transaction->getInvoiceUuid())
                ->create()
                ->getId();
            if (!$refundId) {
                $exceptionMsg = (string)self::EXCEPTION_MSG;
                throw new LocalizedException(__($exceptionMsg, $order->getIncrementId()));
            }
        }

        return [];
    }
}
