<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;

class AuthorizePaymentCommand extends AbstractCommand
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logger
     */
    public function __construct(OrderRepositoryInterface $orderRepository, LogRepository $logger)
    {
        parent::__construct($orderRepository, $logger->addPrefix("AuthorizePaymentCommand"));
    }

    /**
     * @param array $subject
     */
    protected function executeCommand(array $subject): void
    {
        /** @var  Payment $payment */
        $payment = SubjectReader::readPayment($subject)->getPayment();

        // Set the transaction to pending so that the order is created in a pending state
        // The pending state set by magento is not the one we want, so we will overwrite that in OrderPlacedHandler
        // This status will also help third party code that may be listening to transactions.
        $payment->setIsTransactionPending(true);

        // Do not send emails when the order is placed
        // We will instead send emails when the payment is settled
        $payment->getOrder()->setCanSendNewEmailFlag(false);
    }
}
