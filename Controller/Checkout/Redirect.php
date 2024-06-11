<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\Plugin\AuthenticationException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;
use TrueLayer\Connect\Helper\SessionHelper;
use TrueLayer\Connect\Service\Order\HPPService;
use TrueLayer\Connect\Service\Order\PaymentCreationService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentTransactionService;
use TrueLayer\Exceptions\ApiRequestJsonSerializationException;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;


class Redirect extends BaseController
{
    private Session $checkoutSession;
    private OrderRepositoryInterface $orderRepository;
    private PaymentCreationService $paymentCreationService;
    private HPPService $hppService;
    private PaymentTransactionRepositoryInterface $transactionRepository;
    private SessionHelper $sessionHelper;
    private LogService $logger;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCreationService $paymentCreationService
     * @param HPPService $hppService
     * @param PaymentTransactionRepositoryInterface $transactionRepository
     * @param SessionHelper $sessionHelper
     * @param LogService $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        PaymentCreationService $paymentCreationService,
        HPPService $hppService,
        PaymentTransactionRepositoryInterface $transactionRepository,
        SessionHelper $sessionHelper,
        LogService $logger
    )
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->paymentCreationService = $paymentCreationService;
        $this->hppService = $hppService;
        $this->transactionRepository = $transactionRepository;
        $this->sessionHelper = $sessionHelper;
        $this->logger = $logger->prefix('RedirectToHpp');
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
           return $this->createPaymentAndRedirect();
        } catch (\Exception $e) {
            $this->logger->error('Failed to create payment and redirect to HPP', $e);
            $this->failOrder();
            return $this->redirectToFailPage();
        }
    }

    /**
     * @return ResponseInterface
     * @throws ApiRequestJsonSerializationException
     * @throws ApiResponseUnsuccessfulException
     * @throws AuthenticationException
     * @throws InvalidArgumentException
     * @throws LocalizedException
     * @throws SignerException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    private function createPaymentAndRedirect(): ResponseInterface
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $created = $this->paymentCreationService->createPayment($order);
        $url = $this->hppService->getRedirectUrl($created);
        $transaction = $this->transactionRepository->getByPaymentUuid($created->getId());
        $this->sessionHelper->allowQuoteRestoration($transaction->getQuoteId());

        return $this->redirect($url);
    }

    private function failOrder(): void
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $order->setState(Order::STATE_CANCELED)->setStatus(Order::STATE_CANCELED);
        $order->addStatusToHistory($order->getStatus(), 'Failed to create payment and redirect to HPP', true);
        $this->orderRepository->save($order);
    }

    /**
     * @return ResponseInterface
     */
    private function redirectToFailPage(): ResponseInterface
    {
        $this->context->getMessageManager()->addErrorMessage(__('There was an issue creating your payment. Please try again.'));
        return $this->redirect('checkout/cart/index');
    }
}