<?php

/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace TrueLayer\Connect\Block\Checkout;

use TrueLayer\Connect\Service\Order\PaymentErrorMessageManager;

/**
 * System Configuration Heading Block
 */
class PaymentErrorMessage extends \Magento\Framework\View\Element\Template
{
    private $manager;
    public function __construct(\Magento\Framework\View\Element\Template\Context $context, PaymentErrorMessageManager $manager)
    {
        parent::__construct($context);
        $this->manager = $manager;
    }

    public function hasMessage()
    {
        // return true;
        $hasMessage = $this->manager->hasMessage();
        return $hasMessage;
    }

    public function getMessage()
    {
        // return 'there\'s always a message';
        $message = $this->manager->getMessage();
        if ($message) {
            return $message->getData()['text'] ?? null;
        }
    }
}
