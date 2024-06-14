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
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;

class RefundFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private CreditmemoRepositoryInterface $creditmemoRepository;
    private RefundTransactionService $transactionService;
    private LogServiceInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param RefundTransactionService $transactionService
     * @param LogServiceInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        RefundTransactionService $transactionService,
        LogServiceInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->transactionService = $transactionService;
        $this->logger = $logger;
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
        $prefix = "RefundFailedService $refundId";
        $this->logger->addPrefix($prefix)->debug('Start');

        $this->transactionService
            ->refundId($refundId)
            ->execute(function (RefundTransactionDataInterface $transaction) use ($failureReason) {
                $order = $this->orderRepository->get($transaction->getOrderId());
                $creditMemo = $this->getCreditMemo($transaction);
                $this->refundOrder($order, $creditMemo);
                $this->markCreditMemoRefunded($creditMemo, $failureReason);
                $transaction->setRefundFailed();
            });

        $this->logger->removePrefix($prefix);
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
        $amount = "{$creditMemo->getBaseCurrencyCode()}{$creditMemo->getBaseGrandTotal()}";
        $creditMemo->addComment("Refund of $amount failed ($failureReason)");
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
        $totalRefundedOriginal = $order->getBaseTotalRefunded();
        $totalRefunded = $totalRefundedOriginal - $creditMemo->getBaseGrandTotal();
        $order->setBaseTotalRefunded($totalRefunded);

        $order->setTotalRefunded($order->getTotalRefunded() - $creditMemo->getGrandTotal());

        $this->orderRepository->save($order);
        $this->logger->debug('Order refund reversed', [
            'refund_total_original' => $totalRefundedOriginal,
            'refund_total_new' => $totalRefunded
        ]);
    }
}
