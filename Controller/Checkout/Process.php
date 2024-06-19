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
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;

class Process extends BaseController implements HttpGetActionInterface, HttpPostActionInterface
{
    private PageFactory $pageFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param PageFactory $pageFactory
     * @param LogRepository $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        PageFactory $pageFactory,
        LogRepository $logger
    ) {
        $this->pageFactory = $pageFactory;
        parent::__construct($context, $jsonFactory, $logger);
    }

    /**
     * @return ResultInterface
     */
    public function executeAction(): ResultInterface
    {
        return $this->pageFactory->create();
    }
}
