<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Exception;
use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;

abstract class AbstractCommand implements CommandInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    /**
     * @var LogRepository
     */
    private LogRepository $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param LogRepository $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        LogRepository $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @param array $commandSubject
     * @return null
     * @throws Exception
     */
    public function execute(array $commandSubject)
    {
        $this->logger->debug('Start');

        try {
            $this->executeCommand($this->getOrder($commandSubject), $commandSubject);
            $this->logger->debug('Executed');
        } catch (Exception $e) {
            $this->logger->error('Failed executing', $e);
            throw $e;
        }

        return null;
    }

    /**
     * @param array $subject
     * @return OrderInterface
     * @throws Exception
     */
    protected function getOrder(array $subject): OrderInterface
    {
        $orderId = SubjectReader::readPayment($subject)->getOrder()->getId();
        return $this->orderRepository->get($orderId);
    }

    /**
     * @param OrderInterface $order
     * @param array $subject
     * @return mixed
     */
    abstract protected function executeCommand(OrderInterface $order, array $subject): void;
}
