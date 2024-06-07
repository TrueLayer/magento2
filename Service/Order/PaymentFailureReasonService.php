<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

class PaymentFailureReasonService
{
    public function getHumanReadableLabel(string $reason): \Magento\Framework\Phrase
    {
        switch($reason) {
            case 'cancelled':
                return __('You cancelled the payment');
            case 'expired':
                return __('Payment has expired');
            case 'not_authorized':
                return __('Payment was not authorized');
            case 'provider_error':
                return __('Your provider has encountered an error');
            default:
                return __("Payment failed");
        }
    }
}
