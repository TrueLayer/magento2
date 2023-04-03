<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\System;

use TrueLayer\Connect\Api\Config\System\DebugInterface;

/**
 * Debug provider class
 */
class DebugRepository extends SettingsRepository implements DebugInterface
{

    /**
     * @inheritDoc
     */
    public function isDebugLoggingEnabled(): bool
    {
        return $this->isSetFlag(self::XML_PATH_ENABLE_LOGGING);
    }
}
