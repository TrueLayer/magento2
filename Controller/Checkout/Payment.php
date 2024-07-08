<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Service\Order\PaymentCreationService;
use TrueLayer\Exceptions\ApiRequestJsonSerializationException;
use TrueLayer\Exceptions\ApiResponseUnsuccessfulException;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;

class Payment extends BaseController implements HttpPostActionInterface
{
    private Session $checkoutSession;
    private PaymentCreationService $paymentCreationService;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Session $checkoutSession
     * @param PaymentCreationService $paymentCreationService
     * @param LogServiceInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Session $checkoutSession,
        PaymentCreationService $paymentCreationService,
        LogServiceInterface $logger
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentCreationService = $paymentCreationService;
        $logger = $logger->addPrefix('PaymentController');
        parent::__construct($context, $jsonFactory, $logger);
    }

    /**
     * @throws NoSuchEntityException
     * @throws ApiResponseUnsuccessfulException
     * @throws ApiRequestJsonSerializationException
     * @throws SignerException
     * @throws LocalizedException
     * @throws InvalidArgumentException
     * @return ResultInterface
     */
    public function executeAction(): ResultInterface
    {
        // TODO: handle errors

        $payment = $this->paymentCreationService->createPaymentForQuote(
            $this->checkoutSession->getQuote()
        );

        return $this->jsonResponse([
            'payment_id' => $payment->getId(),
            'resource_token' => $payment->getResourceToken(),
        ]);
    }
}
