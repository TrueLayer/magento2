<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;

class ConfigProvider implements ConfigProviderInterface
{
    private SettingsRepositoryInterface $settingsRepository;
    public const CODE = 'truelayer';

    public function __construct(SettingsRepositoryInterface $settingsRepository) {
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Get config
     *
     * @return \array[][]
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => [
                    'isPreselected' => $this->settingsRepository->isPreselected(),
                    'isCheckoutWidgetEnabled' => $this->settingsRepository->isCheckoutWidgetEnabled(),
                    'isCheckoutWidgetSeamless' => $this->settingsRepository->isCheckoutWidgetSeamless(),
                    'isCheckoutWidgetRecommended' => $this->settingsRepository->isCheckoutWidgetRecommended(),
                ]
            ]
        ];
    }
}