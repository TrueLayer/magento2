<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use TrueLayer\Connect\Model\Config\System\SettingsRepository;

/**
 * Payment Config Provider
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var SettingsRepository
     */
    private $settingsRepository;

    public function __construct(
        SettingsRepository $settingsRepository
    ) {
        $this->settingsRepository = $settingsRepository;
    }
    public const CODE = 'truelayer';

    /**
     * Get config
     *
     * @return \array[][]
     */
    public function getConfig(): array
    {
        $description = $this->settingsRepository->getShowDescription()
            ? $this->settingsRepository->getDescription()
            : null;

        return [
            'payment' => [
                self::CODE => [
                    'description' => $description
                ]
            ]
        ];
    }
}
