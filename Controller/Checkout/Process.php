<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Connect\Helper\SessionHelper;
use TrueLayer\Connect\Helper\ValidationHelper;
use TrueLayer\Connect\Model\Config\Repository as ConfigRepository;
use TrueLayer\Connect\Service\Client\ClientFactory;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Helper\PaymentFailureReasonHelper;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentFailedService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentSettledService;
use TrueLayer\Interfaces\Payment\PaymentFailedInterface;
use TrueLayer\Interfaces\Payment\PaymentRetrievedInterface;
use TrueLayer\Interfaces\Payment\PaymentSettledInterface;


/**
 * Process Controller
 */
class Process extends BaseController
{
    private OrderRepositoryInterface $orderRepository;
    private PageFactory $pageFactory;
    private JsonFactory $jsonFactory;
    private PaymentFailedService $paymentFailedService;
    private PaymentSettledService $paymentSettledService;
    private ClientFactory $clientFactory;
    private ConfigRepository $configRepository;
    private TransactionRepository $transactionRepository;
    private SessionHelper $sessionHelper;
    private LogRepository $logger;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        JsonFactory $jsonFactory,
        PageFactory $pageFactory,
        ClientFactory $clientFactory,
        PaymentSettledService $paymentSettledService,
        PaymentFailedService $paymentFailedService,
        ConfigRepository $configRepository,
        TransactionRepository $transactionRepository,
        SessionHelper $sessionHelper,
        LogRepository $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->jsonFactory = $jsonFactory;
        $this->pageFactory = $pageFactory;
        $this->clientFactory = $clientFactory;
        $this->configRepository = $configRepository;
        $this->transactionRepository = $transactionRepository;
        $this->paymentSettledService = $paymentSettledService;
        $this->paymentFailedService = $paymentFailedService;
        $this->sessionHelper = $sessionHelper;
        $this->logger = $logger->addPrefix('Process');
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|ResultInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $paymentId = $this->context->getRequest()->getParam('payment_id');

        // Validate payment id
        if (!$paymentId || !ValidationHelper::isUUID($paymentId)) {
            return $this->noPaymentFoundResponse();
        }

        // Check if we have a final status already in the transactions table and redirect
        if ($redirect = $this->getFinalPaymentStatusResponse($paymentId)) {
            return $redirect;
        }

        // If we reach this point it means the webhook has not been processed yet
        // Check the payment and update the order
        $this->checkPaymentAndUpdateOrder($paymentId);

        // Refresh the transaction and try again
        if ($redirect = $this->getFinalPaymentStatusResponse($paymentId)) {
            return $redirect;
        }

        // No updates available on the payment, show a spinner page and start polling
        return $this->pendingResponse();
    }


    /**
     * @param string $paymentId
     * @return ResponseInterface|JsonResult
     * @throws InputException
     */
    private function getFinalPaymentStatusResponse(string $paymentId)
    {
        try {
            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
        } catch (NoSuchEntityException $e) {
            return $this->noPaymentFoundResponse();
        }

        if ($transaction->getStatus() === 'payment_settled') {
            return $this->urlResponse('checkout/onepage/success');
        }

        if ($transaction->getStatus() === 'payment_failed') {
            $this->sessionHelper->restoreQuote($transaction->getQuoteId());
            $message = PaymentFailureReasonHelper::getHumanReadableLabel($transaction->getFailureReason());
            $this->context->getMessageManager()->addErrorMessage($message . ' ' . __('Please try again.'));
            return $this->urlResponse('checkout/cart');
        }

        return null;
    }

    private function noPaymentFoundResponse()
    {
        $this->logger->error('Could not load TL payment');
        $this->context->getMessageManager()->addErrorMessage(__('No payment found'));
        return $this->urlResponse('checkout/cart');
    }

    /**
     * Render a loading UI
     * @return JsonResult|Page
     */
    private function pendingResponse()
    {
        return $this->expectsJson()
            ? $this->jsonResponse(['pending' => true])
            : $this->pageFactory->create();
    }

    /**
     * Check payment and update order and transaction accordingly
     * @param string $paymentId
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
     * Get a redirect or a json
     * @param string $to
     * @param array $arguments
     * @return ResponseInterface|JsonResult
     */
    private function urlResponse(string $to, array $arguments = [])
    {
        return $this->expectsJson()
            ? $this->jsonResponse(['redirect' => $this->configRepository->getBaseUrl() . $to])
            : $this->redirect($to, $arguments);
    }

    /**
     * @param array $data
     * @return JsonResult
     */
    private function jsonResponse(array $data): JsonResult
    {
        $jsonResponse = $this->jsonFactory->create();
        $jsonResponse->setData($data);
        return $jsonResponse;
    }

    /**
     * @return bool
     */
    private function expectsJson(): bool
    {
        return !empty($this->context->getRequest()->getParam('json'));
    }
}
