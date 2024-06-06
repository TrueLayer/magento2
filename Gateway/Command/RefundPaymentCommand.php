<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Service\Order\PaymentRefundService;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Exceptions\InvalidArgumentException;

class RefundPaymentCommand extends AbstractCommand
{
    /**
     * @var PaymentRefundService
     */
    private PaymentRefundService $refundService;

    /**
     * @param PaymentRefundService $refundService
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logger
     */
    public function __construct(
        PaymentRefundService     $refundService,
        OrderRepositoryInterface $orderRepository,
        LogRepository            $logger
    ) {
        $this->refundService = $refundService;
        parent::__construct($orderRepository, $logger->prefix("RefundPaymentCommand"));
    }

    /**
     * @param OrderInterface $order
     * @param array $subject
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InvalidArgumentException
     */
    protected function executeCommand(OrderInterface $order, array $subject): void
    {
        $this->refundService->execute($order, (float) SubjectReader::readAmount($subject));
    }
}