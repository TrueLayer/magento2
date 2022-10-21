<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Payment Config Provider
 */
class ConfigProvider implements ConfigProviderInterface
{
    public const CODE = 'truelayer';

    /**
     * Get config
     *
     * @return \array[][]
     */
    public function getConfig(): array
    {
        return [
            'payment' => [
                self::CODE => []
            ]
        ];
    }
}
