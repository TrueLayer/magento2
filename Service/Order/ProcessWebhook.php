<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\User\RepositoryInterface as UserRepository;

/**
 * Class ProcessWebhook
 */
class ProcessWebhook
{

    public const SUCCESS_MSG = 'Order #%1 successfully captured on TrueLayer';
    /**
     * @var CheckoutSession
     */
    public $checkoutSession;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * ProcessWebhook constructor.
     *
     * @param CartRepositoryInterface $quoteRepository
     * @param TransactionRepository $transactionRepository
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param ConfigRepository $configRepository
     * @param LogRepository $logRepository
     * @param CheckoutSession $checkoutSession
     * @param UserRepository $userRepository
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        TransactionRepository $transactionRepository,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        ConfigRepository $configRepository,
        LogRepository $logRepository,
        CheckoutSession $checkoutSession,
        UserRepository $userRepository
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->transactionRepository = $transactionRepository;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->checkoutSession = $checkoutSession;
        $this->userRepository = $userRepository;
    }

    /**
     * Place order via webhook
     *
     * @param string $uuid
     * @param string $userId
     */
    public function execute(string $uuid, string $userId)
    {
        $this->logRepository->addDebugLog('webhook payload uuid', $uuid);
        error_log('********* '.$uuid);

        try {
            $transaction = $this->transactionRepository->getByUuid($uuid);

            $this->logRepository->addDebugLog(
                'webhook transaction id',
                $transaction->getEntityId() . ' quote_id = ' . $transaction->getQuoteId()
            );

            if (!$quoteId = $transaction->getQuoteId()) {
                $this->logRepository->addDebugLog('webhook', 'no quote id found in transaction');
                return;
            }

            $quote = $this->quoteRepository->get($quoteId);
            $this->checkoutSession->setQuoteId($quoteId);

            if (!$this->transactionRepository->isLocked($transaction)) {
                $this->logRepository->addDebugLog('webhook', 'start processing accepted transaction');
                $this->transactionRepository->lock($transaction);

                if (!$this->transactionRepository->checkOrderIsPlaced($transaction)) {
                    $orderId = $this->placeOrder($quote, $uuid, $userId);
                    $transaction->setOrderId((int)$orderId)->setStatus('payment_settled');
                    $this->transactionRepository->save($transaction);
                    $this->logRepository->addDebugLog('webhook', 'Order placed. Order id = ' . $orderId);
                }

                $this->transactionRepository->unlock($transaction);
                $this->logRepository->addDebugLog('webhook', 'end processing accepted transaction');
            }
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('webhook exception', $e->getMessage());
        }
    }

    /**
     * @param CartInterface $quote
     * @param $uuid
     * @param $userId
     * @return false|int|null
     */
    private function placeOrder(CartInterface $quote, $uuid, $userId)
    {
        try {
            $quote = $this->prepareQuote($quote, $userId);
            $orderId = $this->cartManagement->placeOrder($quote->getId());
            $order = $this->orderRepository->get($orderId);
            $this->sendOrderEmail($order);

            $payment = $order->getPayment();
            $payment->setTransactionId($uuid);
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($order->getGrandTotal(), true);
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
            $this->sendInvoiceEmail($order);
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('place order', $e->getMessage());
            return false;
        }

        return $order->getEntityId();
    }

    /**
     * Make sure the quote is valid for order placement.
     *
     * Force setCustomerIsGuest; see issue: https://github.com/magento/magento2/issues/23908
     *
     * @param CartInterface $quote
     *
     * @return CartInterface
     */
    private function prepareQuote(CartInterface $quote, string $userId): CartInterface
    {
        if ($quote->getCustomerEmail() == null) {
            $user = $this->userRepository->getByTruelayerId($userId);
            $quote->setCustomerEmail($user['magento_email']);
        }

        $quote->setCustomerIsGuest($quote->getCustomerId() == null);
        $quote->setIsActive(true);
        $quote->getShippingAddress()->setCollectShippingRates(false);
        $this->quoteRepository->save($quote);
        return $quote;
    }

    /**
     * @param OrderInterface $order
     * @return void
     */
    private function sendOrderEmail(OrderInterface $order): void
    {
        if (!$order->getEmailSent() && $this->configRepository->sendOrderEmail()) {
            $this->orderSender->send($order);
            $message = __('New order email sent');
            $order->addStatusToHistory($order->getStatus(), $message, true);
        }
    }

    /**
     * @param OrderInterface $order
     * @return void
     * @throws Exception
     */
    private function sendInvoiceEmail(OrderInterface $order): void
    {
        /** @var Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        if ($invoice && !$invoice->getEmailSent() && $this->configRepository->sendInvoiceEmail()) {
            $this->invoiceSender->send($invoice);
            $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
            $order->addStatusToHistory($order->getStatus(), $message, true);
        }
    }
}
