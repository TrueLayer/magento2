<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * BankingProviders Options Source model
 */
class BankingProviders implements OptionSourceInterface
{
    /**
     * Returns mode option source array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => 'retail', 'label' => __('Retail')],
            ['value' => 'business', 'label' => __('Business')],
            ['value' => 'corporate', 'label' => __('Corporate')]
        ];
    }
}
