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

    public const MESSAGE_GROUP = 'truelayer_payment';
    public const MESSAGE_ID = 'truelayer_payment_error';

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
            ->createMessage(MessageInterface::TYPE_ERROR, self::MESSAGE_ID)
            ->setData(['text' => $text]);

        $this->messageManager->addUniqueMessages([ $message ], self::MESSAGE_GROUP);
    }

    /**
     * @return MessageInterface|null
     */
    public function getMessage(): ?MessageInterface
    {
        $message = $this->messageManager
            ->getMessages(false, self::MESSAGE_GROUP)
            ->getMessageByIdentifier(self::MESSAGE_ID);

        $this->clearMessage();

        return $message;
    }

    public function clearMessage(): void
    {
        $messages = $this->messageManager->getMessages(true, self::MESSAGE_GROUP);
        $messages->deleteMessageByIdentifier(self::MESSAGE_ID);

        if ($messages->getCount()) {
            $this->messageManager->addMessages($messages->getItems(), self::MESSAGE_GROUP);
        }
    }
}
