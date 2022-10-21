<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\ViewModel\Checkout;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * Pending ViewModel
 */
class Pending implements ArgumentInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * Pending constructor.
     *
     * @param RequestInterface $request
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        RequestInterface $request,
        ConfigRepository $configRepository
    ) {
        $this->request = $request;
        $this->configRepository = $configRepository;
    }

    /**
     * Get refresh url
     *
     * @return string
     */
    public function getRefreshUrl(): string
    {
        $paymentId = $this->request->getParam('payment_id');
        return $this->configRepository->getBaseUrl() . "truelayer/checkout/process/payment_id/{$paymentId}/";
    }

    /**
     * Get check url
     *
     * @return string
     */
    public function getCheckUrl(): string
    {
        $token = $this->request->getParam('payment_id');
        return $this->configRepository->getBaseUrl() . "rest/V1/truelayer/check-order-placed/{$token}/";
    }
}
