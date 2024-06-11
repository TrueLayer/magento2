<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\RefundUpdate;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;

class RefundFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private CreditmemoRepositoryInterface $creditmemoRepository;
    private RefundTransactionService $transactionService;
    private LogService $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param RefundTransactionService $transactionService
     * @param LogService $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RefundTransactionService $transactionService,
        LogService $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->transactionService = $transactionService;
        $this->logger = $logger->prefix('RefundFailedService');
    }

    /**
     * @param string $refundId
     * @param string $failureReason
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function handle(string $refundId, string $failureReason)
    {
        $this->logger->prefix($refundId);

        $this->transactionService
            ->logger($this->logger)
            ->refundId($refundId)
            ->execute(function (RefundTransactionDataInterface $transaction) use ($failureReason) {
                $order = $this->orderRepository->get($transaction->getOrderId());
                $creditMemo = $this->getCreditMemo($transaction);
                $this->refundOrder($order, $creditMemo);
                $this->markCreditMemoRefunded($creditMemo, $failureReason);
                $transaction->setStatus('refund_failed');
            });
    }

    /**
     * @param RefundTransactionDataInterface $transaction
     * @return Creditmemo|null
     */
    private function getCreditMemo(RefundTransactionDataInterface $transaction): ?Creditmemo
    {
        if (!$transaction->getCreditMemoId()) {
            return null;
        }

        $creditMemo = $this->creditmemoRepository->get($transaction->getCreditMemoId());

        if (!$creditMemo || !$creditMemo->getEntityId()) {
            return null;
        }

        $this->logger->debug('Creditmemo found', $creditMemo->getEntityId());

        return $creditMemo;
    }

    /**
     * @param Creditmemo $creditMemo
     * @param string $failureReason
     */
    private function markCreditMemoRefunded(Creditmemo $creditMemo, string $failureReason): void
    {
        $creditMemo->addComment("Refund of {$creditMemo->getBaseCurrencyCode()}{$creditMemo->getBaseGrandTotal()} failed ($failureReason)");
        $creditMemo->setGrandTotal(0);
        $creditMemo->setBaseGrandTotal(0);
        $creditMemo->setState(Creditmemo::STATE_CANCELED);

        $this->creditmemoRepository->save($creditMemo);
        $this->logger->debug('Creditmemo updated');
    }

    /**
     * @param OrderInterface $order
     * @param Creditmemo $creditMemo
     */
    private function refundOrder(OrderInterface $order, Creditmemo $creditMemo): void
    {
        $totalRefundedOriginal = $order->getTotalRefunded();
        $totalRefunded = $totalRefundedOriginal - $creditMemo->getBaseGrandTotal();
        $order->setTotalRefunded($totalRefunded);

        $this->orderRepository->save($order);
        $this->logger->debug('Order refund reversed', [
            'refund_total_original' => $totalRefundedOriginal,
            'refund_total_new' => $totalRefunded
        ]);
    }
}