<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\PaymentMethod\AvailabilityChecks;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Quote\Api\Data\CartInterface;
use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;

class CountryCheck extends AbstractCheck
{
    const ALLOWED_COUNTRIES = ['GB'];

    private DirectoryHelper $directoryHelper;
    private SettingsRepositoryInterface $settingsRepository;

    public function __construct(DirectoryHelper $directoryHelper, SettingsRepositoryInterface $settingsRepository)
    {
        $this->directoryHelper = $directoryHelper;
        $this->settingsRepository = $settingsRepository;
    }

    public function isAllowed(CartInterface $quote): bool
    {
        if (!$this->settingsRepository->isCheckoutWidgetEnabled()) {
            return true;
        }

        return in_array(
            $this->directoryHelper->getDefaultCountry($quote->getStoreId()),
            self::ALLOWED_COUNTRIES
        );
    }
}