<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionRepositoryInterface;
use TrueLayer\Connect\Helper\AmountHelper;


class CreditMemoObserver implements ObserverInterface
{
    private RefundTransactionRepositoryInterface $refundTransactionRepository;
    private LogService $logger;

    /**
     * @param RefundTransactionRepositoryInterface $refundTransactionRepository
     * @param LogService $logger
     */
    public function __construct(RefundTransactionRepositoryInterface $refundTransactionRepository, LogService $logger)
    {
        $this->refundTransactionRepository = $refundTransactionRepository;
        $this->logger = $logger->prefix('CreditMemoObserver');
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
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
            $refundTransaction = $this->refundTransactionRepository->getOneByColumns([
                RefundTransactionDataInterface::AMOUNT => AmountHelper::toMinor($creditMemo->getGrandTotal()),
                RefundTransactionDataInterface::ORDER_ID => $order->getEntityId(),
                RefundTransactionDataInterface::CREDITMEMO_ID => ['null' => true],
            ], [RefundTransactionDataInterface::ENTITY_ID => 'DESC']);
        } catch (\Exception $e) {
            $this->logger->error('Failed loading transaction', $e);
            throw $e;
        }

        if (!$refundTransaction || !$refundTransaction->getEntityId()) {
            $refundTransaction = $this->refundTransactionRepository->getByCreditMemoId((int) $creditMemo->getEntityId());
            if ($refundTransaction->getIsLocked()) {
                return;
            }

            $this->logger->error('Transaction not found');
            throw new LocalizedException(__('Something has gone wrong. Please check the refund status in your TrueLayer Console account.'));
        }

        if ($refundTransaction->getCreditMemoId()) {
            return;
        }

        $refundTransaction->setCreditMemoId((int)$creditMemo->getEntityId());
        $this->refundTransactionRepository->save($refundTransaction);
        $this->logger->debug('Refund transaction updated');
    }
}