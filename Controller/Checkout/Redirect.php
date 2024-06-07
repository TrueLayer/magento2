<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Service\Order\HPPService;
use TrueLayer\Connect\Service\Order\PaymentCreationService;


class Redirect implements HttpGetActionInterface
{
    /**
     * @var Context
     */
    private Context $context;

    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var PaymentCreationService
     */
    private PaymentCreationService $paymentCreationService;

    /**
     * @var HPPService
     */
    private HPPService $hppService;

    /**
     * @var LogService
     */
    private LogService $logger;

    /**
     * @param Context $context
     * @param Session $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCreationService $paymentCreationService
     * @param HPPService $hppService
     * @param LogService $logger
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        PaymentCreationService $paymentCreationService,
        HPPService $hppService,
        LogService $logger
    )
    {
        $this->context = $context;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->paymentCreationService = $paymentCreationService;
        $this->hppService = $hppService;
        $this->logger = $logger->prefix('RedirectToHpp');
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        try {
            return $this->redirect($this->createPaymentAndHppUrl());
        } catch (\Exception $e) {
            $this->logger->error('Failed to create payment and redirect to HPP', $e);
            $this->failOrder();
            return $this->redirectToFailPage();
        }
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\Plugin\AuthenticationException
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\InvalidArgumentException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    private function createPaymentAndHppUrl(): string
    {
        $order = $this->checkoutSession->getLastRealOrder();
        $created = $this->paymentCreationService->createPayment($order);
        return $this->hppService->getRedirectUrl($created);
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
        return $this->redirect('checkout/cart');
    }

    /**
     * @param string $to
     * @return ResponseInterface
     */
    private function redirect(string $to): ResponseInterface
    {
        $response = $this->context->getResponse();
        $this->context->getRedirect()->redirect($response, $to, []);
        return $response;
    }
}