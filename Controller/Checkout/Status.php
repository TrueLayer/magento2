<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Connect\Helper\ValidationHelper;
use TrueLayer\Connect\Model\Config\Repository as ConfigRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Helper\PaymentFailureReasonHelper;
use TrueLayer\Connect\Service\Order\PaymentErrorMessageManager;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentFailedService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentSettledService;
use TrueLayer\Interfaces\Payment\PaymentFailedInterface;
use TrueLayer\Interfaces\Payment\PaymentRetrievedInterface;
use TrueLayer\Interfaces\Payment\PaymentSettledInterface;

class Status extends BaseController implements HttpPostActionInterface
{
    const CHECK_API_AFTER_ATTEMPTS = 7;

    private Session $session;
    private OrderRepositoryInterface $orderRepository;
    private PaymentFailedService $paymentFailedService;
    private PaymentSettledService $paymentSettledService;
    private ClientFactory $clientFactory;
    private ConfigRepository $configRepository;
    private TransactionRepository $transactionRepository;
    private PaymentErrorMessageManager $paymentErrorMessageManager;

    public function __construct(
        Context $context,
        Session $session,
        JsonFactory $jsonFactory,
        OrderRepositoryInterface $orderRepository,
        ClientFactory $clientFactory,
        PaymentSettledService $paymentSettledService,
        PaymentFailedService $paymentFailedService,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        PaymentErrorMessageManager $paymentErrorMessageManager,
        LogRepository $logger
    ) {
        $this->session = $session;
        $this->orderRepository = $orderRepository;
        $this->clientFactory = $clientFactory;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->paymentSettledService = $paymentSettledService;
        $this->paymentFailedService = $paymentFailedService;
        $this->paymentErrorMessageManager = $paymentErrorMessageManager;
        $logger->addPrefix('StatusController');
        parent::__construct($context, $jsonFactory, $logger);
    }

    /**
     * @return ResultInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeAction(): ResultInterface
    {
        $paymentId = $this->context->getRequest()->getParam('payment_id');

        // Validate payment id
        if (!ValidationHelper::isUUID($paymentId)) {
            return $this->noPaymentFoundResponse();
        }

        // Check if we have a final status already in the transactions table and redirect
        if ($redirect = $this->getFinalPaymentStatusResponse($paymentId)) {
            return $redirect;
        }

        // Looks like the webhook has not been processed yet
        if ($this->shouldCheckTLApi()) {
            // Check the payment and update the order
            $this->checkPaymentAndUpdateOrder($paymentId);
            if ($redirect = $this->getFinalPaymentStatusResponse($paymentId)) {
                return $redirect;
            }
        }

        // No updates available on the payment, show a spinner page and start polling
        return $this->pendingResponse();
    }


    /**
     * @param string $paymentId
     * @return ResultInterface|null
     * @throws InputException
     */
    private function getFinalPaymentStatusResponse(string $paymentId): ?ResultInterface
    {
        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
        } catch (NoSuchEntityException $e) {
            return $this->noPaymentFoundResponse();
        }

        if ($transaction->isPaymentSettled()) {
            return $this->urlResponse('checkout/onepage/success');
        }

        if ($transaction->isPaymentFailed()) {
            $this->session->restoreQuote();

            $errorText = PaymentFailureReasonHelper::getHumanReadableLabel($transaction->getFailureReason());
            $this->paymentErrorMessageManager->addMessage($errorText . ' ' . __('Please try again.'));

            return $this->urlResponse('checkout/cart');
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
     * @return bool
     */
    private function shouldCheckTLApi(): bool
    {
        $attempt = (int) $this->context->getRequest()->getParam('attempt');
        return $attempt > self::CHECK_API_AFTER_ATTEMPTS;
    }

    /**
     * @return ResultInterface
     */
    private function noPaymentFoundResponse(): ResultInterface
    {
        $this->logger->error('Could not load TL payment');
        $this->context->getMessageManager()->addErrorMessage(__('No payment found'));
        return $this->urlResponse('checkout/cart');
    }

    /**
     * Render a loading UI
     * @return ResultInterface
     */
    private function pendingResponse(): ResultInterface
    {
        return $this->jsonResponse(['pending' => true]);
    }

    /**
     * @param string $to
     * @return ResultInterface
     */
    private function urlResponse(string $to): ResultInterface
    {
        return $this->jsonResponse(['redirect' => $this->configRepository->getBaseUrl() . $to]);
    }
}
