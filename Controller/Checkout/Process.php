<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;
use TrueLayer\Connect\Service\Order\UserReturnService;

class Process extends BaseController implements HttpGetActionInterface, HttpPostActionInterface
{
    private PageFactory $pageFactory;
    private UserReturnService $userReturnService;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param PageFactory $pageFactory
     * @param UserReturnService $userReturnService
     * @param LogRepository $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        PageFactory $pageFactory,
        UserReturnService $userReturnService,
        LogRepository $logger
    ) {
        $this->pageFactory = $pageFactory;
        $this->userReturnService = $userReturnService;
        parent::__construct($context, $jsonFactory, $logger);
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
            (bool) $this->context->getRequest()->getParam('force_api_fallback')
        );

        if ($redirect) {
            return $this->redirect(...$redirect);
        }

        return $this->pageFactory->create();
    }
}
