<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * ReleaseChannel Options Source model
 */
class ReleaseChannel implements OptionSourceInterface
{
    public const GENERAL_AVAILABILITY = 'general_availability';
    public const PUBLIC_BETA = 'public_beta';
    public const PRIVATE_BETA = 'private_beta';

    /**
     * Returns mode option source array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return  [
            ['value' => self::GENERAL_AVAILABILITY, 'label' => __('General Availability')],
            ['value' => self::PUBLIC_BETA, 'label' => __('Public Beta')],
            ['value' => self::PRIVATE_BETA, 'label' => __('Private Beta')]
        ];
    }
}
