<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Gateway\Command;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Connect\Service\Order\HPPService;
use TrueLayer\Connect\Service\Order\PaymentCreationService;
use TrueLayer\Exceptions\InvalidArgumentException;

class CreatePaymentCommand extends AbstractCommand
{
    /**
     * @var CheckoutSession
     */
    private CheckoutSession $checkoutSession;

    /**
     * @var PaymentCreationService
     */
    private PaymentCreationService $orderPaymentService;

    /**
     * @var HPPService
     */
    private HPPService $hppService;

    /**
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PaymentCreationService $orderPaymentService
     * @param HPPService $hppService
     * @param LogRepository $logger
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        PaymentCreationService   $orderPaymentService,
        HPPService $hppService,
        LogRepository            $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderPaymentService = $orderPaymentService;
        $this->hppService = $hppService;
        parent::__construct($orderRepository, $logger->prefix("CreatePaymentCommand"));
    }

    /**
     * @param OrderInterface $order
     * @param array $subject
     * @throws InvalidArgumentException
     * @throws \Magento\Framework\Exception\Plugin\AuthenticationException
     * @throws \TrueLayer\Exceptions\ApiRequestJsonSerializationException
     * @throws \TrueLayer\Exceptions\ApiResponseUnsuccessfulException
     * @throws \TrueLayer\Exceptions\SignerException
     */
    protected function executeCommand(OrderInterface $order, array $subject): void
    {
        $created = $this->orderPaymentService->createPayment($order);
        $hppUrl = $this->hppService->getRedirectUrl($created);
        $this->checkoutSession->setTruelayerHPPRedirect($hppUrl);
    }
}
