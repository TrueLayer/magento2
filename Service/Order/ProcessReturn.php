<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Service\Api\ClientFactory;

/**
 * Class ProcessReturn
 */
class ProcessReturn
{

    public const CANCELLED_MSG = 'Transaction cancelled.';
    public const FAILED_MSG = 'Transaction failed, please try again.';
    public const REJECTED_MSG = 'Transaction rejected please use different method.';
    public const UNKNOWN_MSG = 'Unknown error, please try again.';

    /**
     * @var ClientFactory
     */
    private $clientFactory;
    /**
     * @var OrderInterface
     */
    private $orderInterface;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var LogRepository
     */
    private $logger;

    /**
     * ProcessReturn constructor.
     *
     * @param ClientFactory $clientFactory
     * @param OrderInterface $orderInterface
     * @param TransactionRepository $transactionRepository
     * @param LogRepository $logger
     */
    public function __construct(
        ClientFactory $clientFactory,
        OrderInterface $orderInterface,
        TransactionRepository $transactionRepository,
        LogRepository $logger
    ) {
        $this->clientFactory = $clientFactory;
        $this->orderInterface = $orderInterface;
        $this->transactionRepository = $transactionRepository;
        $this->logger = $logger;
    }

    /**
     * @param string $transactionId
     * @return array
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    public function execute(string $transactionId): array
    {
        $transaction = $this->transactionRepository->getByPaymentUuid($transactionId);
        $order = $this->orderInterface->loadByAttribute('quote_id', $transaction->getQuoteId());

        $client = $this->clientFactory->create((int) $order->getStoreId());
        $paymentStatus = $client->getPayment($transactionId)->getStatus();

        if (!$order->getEntityId()) {
            if ($paymentStatus == 'settled' || $paymentStatus == 'executed') {
                return ['success' => false, 'status' => $paymentStatus];
            } 
        }

        switch ($paymentStatus) {
            case 'executed':
            case 'settled':
                return ['success' => true, 'status' => $paymentStatus];
            case 'cancelled':
                return ['success' => false, 'status' => $paymentStatus, 'msg' => __(self::CANCELLED_MSG)];
            case 'failed':
                return ['success' => false, 'status' => $paymentStatus, 'msg' => __(self::FAILED_MSG)];
            case 'rejected':
                return ['success' => false, 'status' => $paymentStatus, 'msg' => __(self::REJECTED_MSG)];
            default:
                return ['success' => false, 'status' => $paymentStatus, 'msg' => __(self::UNKNOWN_MSG)];
        }
    }
}
