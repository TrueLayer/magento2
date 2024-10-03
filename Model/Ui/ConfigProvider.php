<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use TrueLayer\Connect\Api\Config\System\SettingsRepositoryInterface;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use TrueLayer\Connect\Model\Config\System\SettingsRepository;

class ConfigProvider implements ConfigProviderInterface
{
    private SettingsRepositoryInterface $settingsRepository;
    private ConfigRepositoryInterface $configRepository;
    public const CODE = 'truelayer';

    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        ConfigRepositoryInterface $configRepository
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * Get config
     *
     * @return array{payment:array{truelayer:array{description:string|null}}}
     */
    public function getConfig(): array
    {
        $description = $this->settingsRepository->getShowDescription()
            ? $this->settingsRepository->getDescription()
            : null;

        return [
            'payment' => [
                self::CODE => [
                    'isProduction' => !$this->configRepository->isSandbox(),
                    'isPreselected' => $this->settingsRepository->isPreselected(),
                    'isCheckoutWidgetEnabled' => $this->settingsRepository->isCheckoutWidgetEnabled(),
                    'isCheckoutWidgetRecommended' => $this->settingsRepository->isCheckoutWidgetRecommended(),
                    'description' => $description,
                ]
            ]
        ];
    }
}