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
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionRepositoryInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface as TransactionInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Helper\AmountHelper;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\SignerException;

class RefundService
{
    public const EXCEPTION_MSG = 'Unable to refund order #%1 on TrueLayer';

    private ClientFactory $clientFactory;
    private TransactionRepository $paymentTransactionRepository;
    private RefundTransactionRepositoryInterface $refundTransactionRepository;
    private LogServiceInterface $logger;

    /**
     * @param ClientFactory $clientFactory
     * @param TransactionRepository $paymentTransactionRepository
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param LogServiceInterface $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        TransactionRepository $paymentTransactionRepository,
        RefundTransactionRepositoryInterface $refundTransactionRepository,
        LogServiceInterface $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->paymentTransactionRepository = $paymentTransactionRepository;
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->logger = $logger;
    }

    /**
     * @param OrderInterface $order
     * @param string $invoiceIncrementId
     * @param float $amount
     * @return string|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws SignerException
     */
    public function refund(OrderInterface $order, string $invoiceIncrementId, float $amount): ?string
    {
        $this->logger->addPrefix('RefundService')->debug('Start');

        if ($amount == 0) {
            return null;
        }

        $transaction = $this->paymentTransactionRepository->getByOrderId((int) $order->getEntityId());
        $paymentId = $transaction->getPaymentUuid();

        $amountInMinor = AmountHelper::toMinor($amount);
        $refundId = $this->createRefund($order, $amountInMinor, $transaction, $invoiceIncrementId);
        $this->logger->debug('Created refund', $refundId);

        $refundTransaction = $this->refundTransactionRepository->create()
            ->setOrderId((int) $order->getEntityId())
            ->setPaymentUuid($paymentId)
            ->setRefundUuid($refundId)
            ->setAmount($amountInMinor);

        $this->refundTransactionRepository->save($refundTransaction);
        $this->logger->debug('Refund transaction created', $refundTransaction->getEntityId());

        $this->logger->removePrefix('RefundService');

        return $refundId;
    }

    /**
     * @param OrderInterface $order
     * @param int $amount
     * @param TransactionInterface $transaction
     * @param string $invoiceIncrementId
     * @return string
     * @throws LocalizedException
     * @throws SignerException
     */
    private function createRefund(
        OrderInterface $order,
        int $amount,
        TransactionInterface $transaction,
        string $invoiceIncrementId
    ): string {
        $client = $this->clientFactory->create((int) $order->getStoreId());

        try {
            $refundId = $client->refund()
                ->reference($invoiceIncrementId)
                ->payment($transaction->getPaymentUuid())
                ->amountInMinor($amount)
                ->create()
                ->getId();
        } catch (ApiResponseUnsuccessfulException $e) {
            $this->logger->error('Refund invalid input', $e->getDetail());
            $msg = self::EXCEPTION_MSG . $e->getDetail();
            throw new LocalizedException(__($msg, $order->getIncrementId()));
        } catch (Exception $e) {
            $this->logger->error('Refund failed', $e);
            $msg = self::EXCEPTION_MSG;
            throw new LocalizedException(__($msg, $order->getIncrementId()));
        }

        if (!$refundId) {
            $this->logger->error('No refund ID');
            $msg = self::EXCEPTION_MSG;
            throw new LocalizedException(__($msg, $order->getIncrementId()));
        }

        return $refundId;
    }
}
