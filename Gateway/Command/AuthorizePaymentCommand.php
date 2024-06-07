<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;

class AuthorizePaymentCommand extends AbstractCommand
{
    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logger
     */
    public function __construct(OrderRepositoryInterface $orderRepository, LogRepository $logger)
    {
        parent::__construct($orderRepository, $logger->prefix("AuthorizePaymentCommand"));
    }

    /**
     * @param array $subject
     */
    protected function executeCommand(array $subject): void
    {
        // Set the transaction to pending so that the order is created in a pending state
        // The pending state set by magento is not the one we want, so we will overwrite that in OrderPlacedHandler
        // This status will also help third party code that may be listening to transactions.
        SubjectReader::readPayment($subject)->getPayment()->setIsTransactionPending(true);
    }
}