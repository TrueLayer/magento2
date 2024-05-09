<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Api\CartManagementInterface;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Service\Api\GetClient;

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
     * @var GetClient
     */
    private $getClient;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var OrderInterface
     */
    private $orderInterface;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var LogRepository
     */
    private $logger;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * ProcessReturn constructor.
     *
     * @param Session $checkoutSession
     * @param GetClient $getClient
     * @param CartRepositoryInterface $quoteRepository
     * @param OrderInterface $orderInterface
     * @param OrderRepositoryInterface $orderRepository
     * @param TransactionRepository $transactionRepository
     * @param LogRepository $logger
     */
    public function __construct(
        Session $checkoutSession,
        GetClient $getClient,
        CartRepositoryInterface $quoteRepository,
        OrderInterface $orderInterface,
        OrderRepositoryInterface $orderRepository,
        CartManagementInterface $cartManagement,
        TransactionRepository $transactionRepository,
        LogRepository $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->getClient = $getClient;
        $this->quoteRepository = $quoteRepository;
        $this->orderInterface = $orderInterface;
        $this->orderRepository = $orderRepository;
        $this->cartManagement = $cartManagement;
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
     * @throws \TrueLayer\Exceptions\ValidationException
     */
    public function execute(string $transactionId): array
    {
        $transaction = $this->transactionRepository->getByUuid($transactionId);
        $quote = $this->quoteRepository->get($transaction->getQuoteId());
        $this->checkoutSession->setLoadInactive(true)->replaceQuote($quote);

        $order = $this->orderInterface->loadByAttribute('quote_id', $quote->getId());

        $client = $this->getClient->execute($quote->getStoreId());
        $payment = $client->getPayment($transactionId);
        $transactionStatus = $payment->getStatus();

        if (!$order->getEntityId()) {
            if ($transactionStatus == 'settled' || $transactionStatus == 'executed') {
                return ['success' => false, 'status' => $transactionStatus];
            } 
        }

        switch ($transactionStatus) {
            case 'executed':
            case 'settled':
                $this->updateCheckoutSession($quote, $order);
                return ['success' => true, 'status' => $transactionStatus];
            case 'cancelled':
                $message = (string)self::CANCELLED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            case 'failed':
                $message = (string)self::FAILED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            case 'rejected':
                $message = (string)self::REJECTED_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
            default:
                $message = (string)self::UNKNOWN_MSG;
                return ['success' => false, 'status' => $transactionStatus, 'msg' => __($message)];
        }
    }

    /**
     * @param CartInterface $quote
     * @param Order $order
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    private function updateCheckoutSession(CartInterface $quote, Order $order): void
    {
        $this->orderRepository->save($order);

        // Remove additional quote for customer
        if ($customerId = $quote->getCustomer()->getId()) {
            try {
                $activeQuote = $this->quoteRepository->getActiveForCustomer($customerId);
                $this->quoteRepository->delete($activeQuote);
                $this->cartManagement->createEmptyCartForCustomer($customerId);
            } catch (NoSuchEntityException $e) {
                $this->logger->addErrorLog('Remove customer quote', $e->getMessage());
            }
        }

        $this->checkoutSession->setLastQuoteId($quote->getEntityId())
            ->setLastSuccessQuoteId($quote->getEntityId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderId($order->getId());
    }
}
