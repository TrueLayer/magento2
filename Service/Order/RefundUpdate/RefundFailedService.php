<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\RefundUpdate;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo;
use TrueLayer\Connect\Service\Order\PaymentUpdate\TransactionService;

class RefundFailedService
{
    private OrderRepositoryInterface $orderRepository;
    private CreditmemoRepositoryInterface $creditmemoRepository;
    private TransactionService $transactionService;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param CreditmemoRepositoryInterface $creditmemoRepository
     * @param TransactionService $transactionService
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CreditmemoRepositoryInterface $creditmemoRepository,
        TransactionService $transactionService
    ) {
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->transactionService = $transactionService;
    }

    /**
     * @param string $paymentId
     * @param string $failureReason
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function handle(string $paymentId, string $failureReason)
    {
//        $transaction = $this->transactionService->getTransaction($paymentId);
//        $order = $this->orderRepository->get($transaction->getOrderId());
//        $creditMemo = $this->getCreditMemo($order);
//
//        $this->refundOrder($order, $creditMemo);
//        $this->markCreditMemoRefunded($creditMemo, $failureReason);
    }

    /**
     * @param OrderInterface $order
     * @return Creditmemo|null
     */
    private function getCreditMemo(OrderInterface $order): ?Creditmemo
    {
        if (!$order->hasCreditMemos()) {
            return null;
        }

        /** @var Creditmemo $creditMemo */
        $creditMemo = current($order->getCreditmemosCollection()->getItems());

        return $creditMemo;
    }

    /**
     * @param Creditmemo $creditMemo
     * @param string $failureReason
     */
    private function markCreditMemoRefunded(Creditmemo $creditMemo, string $failureReason): void
    {
        $creditMemo->addComment("Refund failed ($failureReason)");
        $creditMemo->setGrandTotal(0);
        $creditMemo->setBaseGrandTotal(0);
        $this->creditmemoRepository->save($creditMemo);
    }

    /**
     * @param OrderInterface $order
     * @param Creditmemo $creditMemo
     */
    private function refundOrder(OrderInterface $order, Creditmemo $creditMemo): void
    {
        $order->setTotalRefunded($order->getTotalRefunded() - $creditMemo->getBaseGrandTotal());
        $this->orderRepository->save($order);
    }
}