<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Controller\Result\Json as JsonResult;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;

abstract class BaseController
{
    const CACHE_CONTROL = 'no-store, no-cache, must-revalidate, max-age=0';

    protected Context $context;
    protected LogRepository $logger;
    private JsonFactory $jsonFactory;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LogRepository $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LogRepository $logger
    ) {
        $this->context = $context;
        $this->logger = $logger;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function execute()
    {
        $this->logger->debug('Execute');
        $result = $this->executeAction();

        // Prevent caching
        $result->setHeader('Cache-Control', self::CACHE_CONTROL, true);

        return $result;
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    protected abstract function executeAction();

    /**
     * @param string $to
     * @param array $arguments
     * @return ResponseInterface
     */
    protected function redirect(string $to, array $arguments = []): ResponseInterface
    {
        $response = $this->context->getResponse();
        $this->context->getRedirect()->redirect($response, $to, $arguments);
        return $response;
    }

    /**
     * @param array $data
     * @return JsonResult
     */
    protected function jsonResponse(array $data): AbstractResult
    {
        return $this->jsonFactory->create()->setData($data);
    }
}
