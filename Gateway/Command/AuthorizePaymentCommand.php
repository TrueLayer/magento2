<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;

class AuthorizePaymentCommand extends AbstractCommand
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LogRepository            $logger
    ) {
        parent::__construct($orderRepository, $logger->prefix("AuthorizePaymentCommand"));
    }

    /**
     * @param OrderInterface $order
     * @param array $subject
     */
    protected function executeCommand(OrderInterface $order, array $subject): void
    {
        $payment = SubjectReader::readPayment($subject)->getPayment();
        $payment->setIsTransactionPending(true);

        $order
            ->setState(Order::STATE_PENDING_PAYMENT)
            ->setStatus(Order::STATE_PENDING_PAYMENT);

        $this->orderRepository->save($order);
    }
}