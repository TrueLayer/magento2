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
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;
use Magento\Checkout\Model\Session;

class Pending extends BaseController implements HttpGetActionInterface, HttpPostActionInterface
{
    private Session $session;
    private PageFactory $pageFactory;

    /**
     * @param Context $context
     * @param Session $session
     * @param JsonFactory $jsonFactory
     * @param PageFactory $pageFactory
     * @param LogRepository $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        JsonFactory $jsonFactory,
        PageFactory $pageFactory,
        LogRepository $logger
    ) {
        $this->session = $session;
        $this->pageFactory = $pageFactory;
        parent::__construct($context, $jsonFactory, $logger);
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function executeAction(): ResultInterface|ResponseInterface
    {
        $this->session->setQuoteId(null);
        return $this->pageFactory->create();
    }
}
