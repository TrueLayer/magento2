<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
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
 * Class ProcessSettledWebhook
 */
class ProcessSettledWebhook
{

    public const SUCCESS_MSG = 'Order #%1 successfully captured on TrueLayer';

    private TransactionRepository $transactionRepository;

    private OrderRepositoryInterface $orderRepository;

    private InvoiceSender $invoiceSender;

    private OrderSender $orderSender;

    private ConfigRepository $configRepository;

    private LogRepository $logRepository;

    private UserRepository $userRepository;

    /**
     * ProcessSettledWebhook constructor.
     *
     * @param TransactionRepository $transactionRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param ConfigRepository $configRepository
     * @param LogRepository $logRepository
     * @param UserRepository $userRepository
     */
    public function __construct(
        TransactionRepository $transactionRepository,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        ConfigRepository $configRepository,
        LogRepository $logRepository,
        UserRepository $userRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->configRepository = $configRepository;
        $this->logRepository = $logRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @param string $uuid
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(string $uuid)
    {
        $this->logRepository->addDebugLog('webhook - settled payment - start processing ', $uuid);

        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($uuid);

            $this->logRepository->addDebugLog('webhook', [
                'transaction id' => $transaction->getEntityId(),
                'order id' => $transaction->getOrderId(),
            ]);

            if (!$transaction->getOrderId()) {
                $this->logRepository->addDebugLog('webhook', 'aborting, no order found');
                return;
            }

            if ($transaction->getIsLocked()) {
                $this->logRepository->addDebugLog('webhook', 'aborting, transaction is locked');
                return;
            }

            $this->logRepository->addDebugLog('webhook', 'locking transaction and starting order update');
            $this->transactionRepository->lock($transaction);
            $order = $this->orderRepository->get($transaction->getOrderId());

            // Update payment and order status
            $payment = $order->getPayment();
            $payment->setTransactionId($uuid);
            $payment->setIsTransactionClosed(true);
            $payment->registerCaptureNotification($order->getGrandTotal(), true);
            $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
            $this->orderRepository->save($order);
            $this->logRepository->addDebugLog('webhook', 'payment and order statuses updated');

            // Send emails
            $this->sendOrderEmail($order);
            $this->sendInvoiceEmail($order);
            $this->logRepository->addDebugLog('webhook', 'emails sent');

            // Update transaction statis
            $transaction->setStatus('payment_settled');

            $this->transactionRepository->save($transaction);
            $this->transactionRepository->unlock($transaction);
            $this->logRepository->addDebugLog('webhook', 'transaction status set to settled');
        } catch (Exception $e) {
            $this->logRepository->addDebugLog('webhook - exception', $e->getMessage());
            throw $e;
        }
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
