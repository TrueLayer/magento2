<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\Plugin\AuthenticationException;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Service\Order\HPPService;
use TrueLayer\Connect\Service\Order\PaymentCreationService;
use TrueLayer\Connect\Service\Order\PaymentErrorMessageManager;
use TrueLayer\Exceptions\ApiRequestJsonSerializationException;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;

class Redirect extends BaseController implements HttpGetActionInterface
{
    private Session $checkoutSession;
    private OrderRepositoryInterface $orderRepository;
    private PaymentCreationService $paymentCreationService;
    private PaymentErrorMessageManager $paymentErrorMessageManager;
    private HPPService $hppService;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCreationService $paymentCreationService
     * @param PaymentErrorMessageManager $paymentErrorMessageManager
     * @param HPPService $hppService
     * @param LogServiceInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        PaymentCreationService $paymentCreationService,
        PaymentErrorMessageManager $paymentErrorMessageManager,
        HPPService $hppService,
        LogServiceInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->paymentCreationService = $paymentCreationService;
        $this->paymentErrorMessageManager = $paymentErrorMessageManager;
        $this->hppService = $hppService;
        $logger = $logger->addPrefix('RedirectController');
        parent::__construct($context, $jsonFactory, $logger);
    }

    /**
     * @return ResponseInterface
     */
    public function executeAction(): ResponseInterface
    {
        try {
            return $this->createPaymentAndRedirect();
        } catch (Exception $e) {
            $this->logger->error('Failed to create payment and redirect to HPP', $e);
            $this->failOrder();
            $this->checkoutSession->restoreQuote();
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
        $this->logger->addPrefix("order {$order->getEntityId()}");

        $created = $this->paymentCreationService->createPayment($order);
        $url = $this->hppService->getRedirectUrl($created);

        return $this->redirect($url);
    }

    private function failOrder(): void
    {
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order->isCanceled()) {
            $order->cancel();
            $this->logger->debug('Order cancelled, failed to create payment');
        }
        $order->addStatusToHistory($order->getStatus(), 'Failed to create payment and redirect to HPP', true);
        $this->orderRepository->save($order);
    }

    /**
     * @return ResponseInterface
     */
    private function redirectToFailPage(): ResponseInterface
    {
        $this->paymentErrorMessageManager->addMessage('There was an issue creating your payment. Please try again.');
        return $this->redirect('checkout/cart/index');
    }
}
