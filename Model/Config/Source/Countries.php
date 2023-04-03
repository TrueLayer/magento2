<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Countries Options Source model
 */
class Countries implements OptionSourceInterface
{
    /**
     * Returns mode option source array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => 'GB', 'label' => __('United Kingdom')],
            ['value' => 'IE', 'label' => __('Ireland')],
            ['value' => 'ES', 'label' => __('Spain')],
            ['value' => 'FR', 'label' => __('France')],
            ['value' => 'DE', 'label' => __('Germany')],
            ['value' => 'NL', 'label' => __('Netherlands')],
            ['value' => 'LT', 'label' => __('Lithuania')]
        ];
    }
}
