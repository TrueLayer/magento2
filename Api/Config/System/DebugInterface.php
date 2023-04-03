<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Config\System;

/**
 * Debug group config repository interface
 * @api
 */
interface DebugInterface extends SettingInterface
{

    /** Debug Group */
    public const XML_PATH_ENABLE_LOGGING = 'payment/truelayer/logging';

    /**
     * Check if we need to log debug calls
     *
     * @return bool
     */
    public function isDebugLoggingEnabled(): bool;
}
