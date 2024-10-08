<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order\PaymentUpdate;

use Exception;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;

class PaymentSettledService
{
    private InvoiceSender $invoiceSender;
    private OrderSender $orderSender;
    private ConfigRepository $configRepository;
    private OrderRepositoryInterface $orderRepository;
    private PaymentTransactionService $transactionService;
    private LogServiceInterface $logger;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderSender $orderSender
     * @param InvoiceSender $invoiceSender
     * @param ConfigRepository $configRepository
     * @param PaymentTransactionService $transactionService
     * @param LogServiceInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface   $orderRepository,
        OrderSender                $orderSender,
        InvoiceSender              $invoiceSender,
        ConfigRepository           $configRepository,
        PaymentTransactionService $transactionService,
        LogServiceInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->configRepository = $configRepository;
        $this->transactionService = $transactionService;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentId
     * @throws InputException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     * @throws Exception
     */
    public function handle(string $paymentId): void
    {
        $prefix = "PaymentSettledService $paymentId";
        $this->logger->addPrefix($prefix);

        $this->transactionService
            ->paymentId($paymentId)
            ->execute(function (PaymentTransactionDataInterface $transaction) use ($paymentId) {
                $order = $this->orderRepository->get($transaction->getOrderId());
                $this->updateOrder($order, $paymentId);
                $transaction->setPaymentSettled();
                $this->sendOrderEmail($order);
                $this->sendInvoiceEmail($order);
            });

        $this->logger->removePrefix($prefix);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function updateOrder(OrderInterface $order, string $paymentId): void
    {
        // Update order payment
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $order->getPayment();
        $payment->setTransactionId($paymentId);
        $payment->setIsTransactionClosed(true);
        $payment->registerCaptureNotification($order->getGrandTotal(), true);

        // Update order state & status
        $order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
        $this->orderRepository->save($order);
        $this->logger->debug('Payment and order statuses updated');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function sendOrderEmail(OrderInterface $order): void
    {
        if ($order->getEmailSent()) {
            $this->logger->debug('Order email already sent');
            return;
        }

        if (!$this->configRepository->sendOrderEmail()) {
            $this->logger->debug('Order email not enabled');
            return;
        }

        $this->orderSender->send($order);
        $this->logger->debug('Order email sent');

        $order->addStatusToHistory($order->getStatus(), __('New order email sent'), true);
        $this->orderRepository->save($order);
        $this->logger->debug('Order note added');
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @throws Exception
     */
    private function sendInvoiceEmail(OrderInterface $order): void
    {
        /** @var Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getFirstItem();

        if (!$invoice) {
            $this->logger->debug('Invoice not found');
            return;
        }

        if ($invoice->getEmailSent()) {
            $this->logger->debug('Invoice email already sent');
            return;
        }

        if (!$this->configRepository->sendInvoiceEmail()) {
            $this->logger->debug('Invoice email not enabled');
            return;
        }

        $this->invoiceSender->send($invoice);
        $this->logger->debug('Invoice email sent');

        $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
        $order->addStatusToHistory($order->getStatus(), $message, true);
        $this->orderRepository->save($order);
        $this->logger->debug('Order note added');
    }
}
