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
use TrueLayer\Connect\Service\Order\RefundService;
use TrueLayer\Connect\Api\Log\LogService;

class RefundPaymentCommand extends AbstractCommand
{
    private RefundService $refundService;

    /**
     * @param RefundService $refundService
     * @param OrderRepositoryInterface $orderRepository
     * @param LogService $logger
     */
    public function __construct(
        RefundService            $refundService,
        OrderRepositoryInterface $orderRepository,
        LogService               $logger
    ) {
        $this->refundService = $refundService;
        parent::__construct($orderRepository, $logger->prefix("RefundPaymentCommand"));
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
        $order = $this->getOrder($subject);
        $this->refundService->refund($order, (float) SubjectReader::readAmount($subject));
    }
}