<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Interfaces\Payment\PaymentCreatedInterface;

class HPPService
{
    /**
     * @var ConfigRepository
     */
    private ConfigRepository $configRepository;

    /**
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @param PaymentCreatedInterface $paymentCreated
     * @return string
     */
    public function getRedirectUrl(PaymentCreatedInterface $paymentCreated): string
    {
        return $paymentCreated->hostedPaymentsPage()
            ->returnUri($this->configRepository->getBaseUrl() . 'truelayer/checkout/process/')
            ->primaryColour($this->configRepository->getPaymentPagePrimaryColor())
            ->secondaryColour($this->configRepository->getPaymentPageSecondaryColor())
            ->tertiaryColour($this->configRepository->getPaymentPageTertiaryColor())
            ->toUrl();
    }
}
