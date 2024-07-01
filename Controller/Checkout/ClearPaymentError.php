<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use TrueLayer\Connect\Service\Order\PaymentErrorMessageManager;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;

class ClearPaymentError extends BaseController implements HttpPostActionInterface
{
    private PaymentErrorMessageManager $manager;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LogRepository $logger
     * @param PaymentErrorMessageManager $manager
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LogRepository $logger,
        PaymentErrorMessageManager $manager
    ) {
        parent::__construct($context, $jsonFactory, $logger);
        $this->manager = $manager;
    }

    /**
     * @return ResultInterface
     */
    public function executeAction(): ResultInterface
    {
        $this->manager->clearMessage();
        return $this->jsonResponse(['done' => true]);
    }
}
