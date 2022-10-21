<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Mode Options Source model
 */
class Mode implements OptionSourceInterface
{

    public const SANDBOX = 'sandbox';
    public const PRODUCTION = 'production';

    /**
     * Returns mode option source array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => self::SANDBOX, 'label' => __('Sandbox')],
            ['value' => self::PRODUCTION, 'label' => __('Production')]
        ];
    }
}
