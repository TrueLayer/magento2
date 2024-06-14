<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Order;

use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;

class PaymentErrorMessageManager
{
    private ManagerInterface $messageManager;

    /**
     * @param ManagerInterface $messageManager
     */
    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * Add a unique message using our custom truelayer payment error template
     * This will trigger a cart and checkout-data refresh on the frontend
     * @param string $text
     */
    public function addMessage(string $text): void
    {
        $message = $this->messageManager
            ->createMessage(MessageInterface::TYPE_ERROR, 'truelayer_payment_error')
            ->setData(['text' => $text]);

        $this->messageManager->addUniqueMessages([ $message ]);
    }
}
