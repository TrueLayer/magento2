<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Service\Order\ProcessReturn;

/**
 * Process Controller
 */
class Process extends Action implements HttpGetActionInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ProcessReturn
     */
    private $processReturn;

    /**
     * Process constructor.
     *
     * @param Context $context
     * @param ProcessReturn $processReturn
     * @param LogRepository $logRepository
     */
    public function __construct(
        Context $context,
        ProcessReturn $processReturn,
        LogRepository $logRepository
    ) {
        parent::__construct($context);
        $this->processReturn = $processReturn;
        $this->logRepository = $logRepository;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$transactionId = $this->getRequest()->getParam('payment_id')) {
            $this->messageManager->addErrorMessage(__('Error in return data from TrueLayer'));
            $resultRedirect->setPath('checkout/cart/index');
            return $resultRedirect;
        }

        try {
            $result = $this->processReturn->execute((string)$transactionId);
            if ($result['success']) {
                $resultRedirect->setPath('checkout/onepage/success');
            } elseif (in_array($result['status'], ['settled', 'executed', 'authorized'])) {
                $resultRedirect->setPath('truelayer/checkout/pending', ['payment_id' => $transactionId]);
            } else {
                $this->messageManager->addErrorMessage('Something went wrong');
                $resultRedirect->setPath('checkout/cart/index');
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Checkout Process', $exception->getMessage());
            $this->messageManager->addErrorMessage('Error processing payment');
            $resultRedirect->setPath('checkout/cart/index');
        }

        return $resultRedirect;
    }
}
