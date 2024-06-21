<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionRepositoryInterface;
use TrueLayer\Connect\Helper\AmountHelper;

class CreditMemoObserver implements ObserverInterface
{
    private RefundTransactionRepositoryInterface $refundTransactionRepository;
    private LogServiceInterface $logger;

    /**
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param LogServiceInterface $logger
     */
    public function __construct(RefundTransactionRepositoryInterface $refundTransactionRepository, LogServiceInterface $logger)
    {
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->logger = $logger->addPrefix('CreditMemoObserver');
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     * @throws Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Order\Creditmemo $creditMemo */
        $creditMemo = $observer->getData('creditmemo');
        $order = $creditMemo->getOrder();

        if ($order->getPayment()->getMethod() !== 'truelayer') {
            return;
        }

        $this->logger->debug('Get transaction');

        try {
            // Find matching transaction with missing creditmemo id
            $refundTransaction = $this->refundTransactionRepository->getOneByColumns([
                RefundTransactionDataInterface::AMOUNT => AmountHelper::toMinor($creditMemo->getGrandTotal()),
                RefundTransactionDataInterface::ORDER_ID => $order->getEntityId(),
                RefundTransactionDataInterface::CREDITMEMO_ID => ['null' => true],
            ], [RefundTransactionDataInterface::ENTITY_ID => 'DESC']);
        } catch (Exception $e) {
            $this->logger->error('Failed loading transaction', $e);
            throw $e;
        }

        if (!$refundTransaction || !$refundTransaction->getEntityId()) {
            if ($this->findByCreditMemo($creditMemo)) {
                return; // We already have a credit memo id, we can abort.
            }
            $this->logger->error('Transaction not found');
            throw new LocalizedException(
                __('Something has gone wrong. Please check the refund status in your TrueLayer Console account.')
            );
        }

        $refundTransaction->setCreditMemoId((int) $creditMemo->getEntityId());
        $this->refundTransactionRepository->save($refundTransaction);
        $this->logger->debug('Transaction updated');
    }

    /**
     * @param Order\Creditmemo $creditMemo
     * @return RefundTransactionDataInterface|null
     */
    private function findByCreditMemo(Order\Creditmemo $creditMemo): ?RefundTransactionDataInterface
    {
        try {
            $creditMemoId = (int) $creditMemo->getEntityId();
            return $this->refundTransactionRepository->getByCreditMemoId($creditMemoId);
        } catch (NoSuchEntityException $e) {
            $this->logger->debug('No transaction found with creditmemo id', $e);
            return null;
        }
    }
}
