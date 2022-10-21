<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Plugin\Quote;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ChangeQuoteControl as Subject;

/**
 * Plugin for ChangeQuoteControl
 * On webhook place order in some cases it's not possible to change quote
 * This plugin is always allow change quote control for truelayer
 */
class ChangeQuoteControl
{
    /**
     * @param Subject $subject
     * @param bool $result
     * @param CartInterface $quote
     * @return bool
     */
    public function afterIsAllowed(
        Subject $subject,
        bool $result,
        CartInterface $quote
    ) {
        if ($quote->getPayment()->getMethod() == 'truelayer') {
            return true;
        }
        return $result;
    }
}
