<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Log;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

/**
 * Debug logger handler class
 */
class DebugHandler extends Base
{

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/truelayer/debug.log';
}
