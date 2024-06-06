<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Checkout;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Service\Api\ClientFactory;


class RedirectToHpp extends Action implements HttpGetActionInterface
{
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var ClientFactory
     */
    private ClientFactory $clientFactory;

    /**
     * @var LogService
     */
    private LogService $logger;

    /**
     * @param Session $checkoutSession
     * @param ClientFactory $clientFactory
     * @param LogService $logger
     */
    public function __construct(Session $checkoutSession, ClientFactory $clientFactory, LogService $logger)
    {
        $this->checkoutSession = $checkoutSession;
        $this->clientFactory = $clientFactory;
        $this->logger = $logger->prefix('RedirectToHpp');
    }

    /**
     * @return ResponseInterface
     */
    public function execute(): ResponseInterface
    {
        $hppUrl = $this->checkoutSession->getTruelayerHPPRedirect();

        if (!$hppUrl || !is_string($hppUrl)) {
            $this->logger->debug('No HPP url found');
            return $this->_redirect('checkout/cart');
        }

        $this->logger->debug('Redirecting to HPP');
        return $this->_redirect($hppUrl);
    }
}