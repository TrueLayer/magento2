<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Service\Order\UserReturnService;

class Status extends BaseController implements HttpPostActionInterface
{
    private const CHECK_API_AFTER_ATTEMPTS = 7;

    private UrlInterface $urlBuilder;
    private UserReturnService $userReturnService;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        UrlInterface $urlBuilder,
        UserReturnService $userReturnService,
        LogServiceInterface $logger
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->userReturnService = $userReturnService;
        parent::__construct($context, $jsonFactory, $logger->addPrefix('StatusController'));
    }

    /**
     * @return ResultInterface|ResponseInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function executeAction(): ResultInterface|ResponseInterface
    {
        $redirect = $this->userReturnService->checkPaymentAndProcessOrder(
            $this->context->getRequest()->getParam('payment_id'),
            $this->shouldFallbackOnTlApi()
        );

        return $this->jsonResponse([
            'redirect' => $redirect ? $this->urlBuilder->getUrl(...$redirect) : null
        ]);
    }

    /**
     * @return bool
     */
    private function shouldFallbackOnTlApi(): bool
    {
        $attempt = (int) $this->context->getRequest()->getParam('attempt');
        return $attempt > self::CHECK_API_AFTER_ATTEMPTS;
    }
}
