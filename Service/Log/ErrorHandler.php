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
 * Error logger handler class
 */
class ErrorHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::ERROR;

    /**
     * @var string
     */
    protected $fileName = '/var/log/truelayer/error.log';
}
