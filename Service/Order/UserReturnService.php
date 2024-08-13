<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Helper\ValidationHelper;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Helper\PaymentFailureReasonHelper;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentFailedService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentSettledService;
use TrueLayer\Interfaces\Payment\PaymentFailedInterface;
use TrueLayer\Interfaces\Payment\PaymentRetrievedInterface;
use TrueLayer\Interfaces\Payment\PaymentSettledInterface;

class UserReturnService
{
    private Context $context;
    private Session $session;
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $quoteRepository;
    private PaymentFailedService $paymentFailedService;
    private PaymentSettledService $paymentSettledService;
    private ClientFactory $clientFactory;
    private TransactionRepository $transactionRepository;
    private PaymentErrorMessageManager $paymentErrorMessageManager;
    private LogServiceInterface $logger;

    public function __construct(
        Context $context,
        Session $session,
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $quoteRepository,
        ClientFactory $clientFactory,
        PaymentSettledService $paymentSettledService,
        PaymentFailedService $paymentFailedService,
        TransactionRepository $transactionRepository,
        PaymentErrorMessageManager $paymentErrorMessageManager,
        LogServiceInterface $logger
    ) {
        $this->context = $context;
        $this->session = $session;
        $this->orderRepository = $orderRepository;
        $this->quoteRepository = $quoteRepository;
        $this->clientFactory = $clientFactory;
        $this->transactionRepository = $transactionRepository;
        $this->paymentSettledService = $paymentSettledService;
        $this->paymentFailedService = $paymentFailedService;
        $this->paymentErrorMessageManager = $paymentErrorMessageManager;
        $this->logger = $logger;
    }

    /**
     * @param string $paymentId
     * @param $shouldFallbackOnTlApi
     * @return string|null
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function checkPaymentAndProcessOrder(string $paymentId, $shouldFallbackOnTlApi): ?string
    {
        // Validate payment id
        if (!ValidationHelper::isUUID($paymentId)) {
            return $this->noPaymentFoundResponse();
        }

        // Check if we have a final status already in the transactions table and redirect
        if ($redirect = $this->getFinalPaymentStatusRedirect($paymentId)) {
            return $redirect;
        }

        // Looks like the webhook has not been processed yet
        if ($shouldFallbackOnTlApi) {
            // Check the payment and update the order
            $this->checkPaymentAndUpdateOrder($paymentId);
            if ($redirect = $this->getFinalPaymentStatusRedirect($paymentId)) {
                return $redirect;
            }
        }

        // No updates available on the payment
        return null;
    }

    /**
     * @param string $paymentId
     * @return string|null
     * @throws InputException
     */
    private function getFinalPaymentStatusRedirect(string $paymentId): ?string
    {
        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
        } catch (NoSuchEntityException $e) {
            return $this->noPaymentFoundResponse();
        }

        if ($transaction->isPaymentSettled()) {
            $quote = $this->quoteRepository->get($this->session->getQuoteId());
            $quote->setIsActive(0);
            $this->quoteRepository->save($quote);
            $this->session->setQuoteId(null);
            return 'checkout/onepage/success';
        }

        if ($transaction->isPaymentFailed()) {
            $errorText = PaymentFailureReasonHelper::getHumanReadableLabel($transaction->getFailureReason());
            $this->paymentErrorMessageManager->addMessage($errorText . ' ' . __('Please try again.'));

            return 'checkout/cart';
        }

        return null;
    }

    /**
     * Check payment and update order and transaction accordingly
     * @param string $paymentId
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    private function checkPaymentAndUpdateOrder(string $paymentId): void
    {
        $payment = $this->getTruelayerPayment($paymentId);

        if ($payment instanceof PaymentSettledInterface) {
            $this->paymentSettledService->handle($paymentId);
        }

        if ($payment instanceof PaymentFailedInterface) {
            $this->paymentFailedService->handle($paymentId, $payment->getFailureReason());
        }
    }

    /**
     * @param string $paymentId
     * @return PaymentRetrievedInterface|null
     */
    private function getTruelayerPayment(string $paymentId): ?PaymentRetrievedInterface
    {
        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
            $order = $this->orderRepository->get($transaction->getOrderId());
            $client = $this->clientFactory->create((int)$order->getStoreId());
            return $client->getPayment($paymentId);
        } catch (Exception $e) {
            $this->logger->error('Could not load TL payment', $e);
        }

        return null;
    }

    /**
     * @return string
     */
    private function noPaymentFoundResponse(): string
    {
        $this->logger->error('Could not load TL payment');
        $this->context->getMessageManager()->addErrorMessage(__('No payment found'));
        return 'checkout/cart';
    }
}
