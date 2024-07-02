<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Helper;

use Magento\Framework\Phrase;

class PaymentFailureReasonHelper
{
    public static function getHumanReadableLabel(string $reason = null): Phrase
    {
        switch ($reason) {
            case 'canceled':
                return __('You cancelled the payment.');
            case 'expired':
                return __('Payment has expired.');
            case 'not_authorized':
                return __('Payment was not authorized.');
            case 'authorization_failed':
                return __('Payment authorisation failed.');
            case 'provider_error':
                return __('Your provider has encountered an error.');
            case 'provider_rejected':
                return __('Your provider rejected the payment.');
            default:
                return __("Payment failed.");
        }
    }
}
