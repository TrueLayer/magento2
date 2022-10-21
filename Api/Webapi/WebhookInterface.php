<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Webapi;

use Magento\Framework\Exception\LocalizedException;

/**
 * Process Webhook data
 * @api
 */
interface WebhookInterface
{

    /**
     * @throws LocalizedException
     * @return void
     */
    public function processTransfer();
}
