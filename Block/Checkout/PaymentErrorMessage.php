<?php

/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace TrueLayer\Connect\Block\Checkout;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use TrueLayer\Connect\Service\Order\PaymentErrorMessageManager;

class PaymentErrorMessage extends Template
{
    private PaymentErrorMessageManager $manager;

    /**
     * @param Context $context
     * @param PaymentErrorMessageManager $manager
     */
    public function __construct(Context $context, PaymentErrorMessageManager $manager)
    {
        parent::__construct($context);
        $this->manager = $manager;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        $message = $this->manager->getMessage();

        if ($message) {
            return $message->getData()['text'] ?: null;
        }

        return null;
    }
}
