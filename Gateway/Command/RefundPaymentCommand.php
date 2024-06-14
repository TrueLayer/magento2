<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use TrueLayer\Connect\Service\Order\RefundService;
use TrueLayer\Connect\Api\Log\LogServiceInterface;

class RefundPaymentCommand extends AbstractCommand
{
    private RefundService $refundService;

    /**
     * @param RefundService $refundService
     * @param OrderRepositoryInterface $orderRepository
     * @param LogServiceInterface $logger
     */
    public function __construct(
        RefundService $refundService,
        OrderRepositoryInterface $orderRepository,
        LogServiceInterface $logger
    ) {
        $this->refundService = $refundService;
        parent::__construct($orderRepository, $logger->addPrefix("RefundPaymentCommand"));
    }

    /**
     * @param array $subject
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function executeCommand(array $subject): void
    {
        /** @var  Payment $payment */
        $payment = SubjectReader::readPayment($subject)->getPayment();
        $invoiceIncrementId = $payment->getCreditMemo()->getInvoice()->getIncrementId();

        $order = $this->getOrder($subject);
        $this->logger->addPrefix($order->getEntityId());
        $this->refundService->refund($order, $invoiceIncrementId, (float) SubjectReader::readAmount($subject));
    }
}
