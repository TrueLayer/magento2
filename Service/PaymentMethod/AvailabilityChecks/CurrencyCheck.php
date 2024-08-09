<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;
use TrueLayer\Interfaces\Payment\PaymentCreatedInterface;

class CurrencyCheck extends AbstractCheck
{
    private ConfigInterface $config;
    private SettingsRepositoryInterface $settingsRepository;

    public function __construct(
        ConfigInterface $config,
        SettingsRepositoryInterface $settingsRepository
    ) {
        $this->config = $config;
        $this->settingsRepository = $settingsRepository;
    }

    public function isAllowed(StoreInterface $store, CartInterface $quote): bool
    {
        $allowedCurrencies = $this->settingsRepository->isCheckoutWidgetEnabled()
            ? ['GB']
            : $this->config->getValue('currency', $store->getId());


        return in_array($quote->getBaseCurrencyCode(), $allowedCurrencies);
    }
}