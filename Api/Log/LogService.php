<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Log;

/**
 * Log repository interface
 * @api
 */
interface LogService
{
    /**
     * Add record to error log
     *
     * @param string $type
     * @param mixed $data
     */
    public function error(string $type, $data): LogService;

    /**
     * Add record to debug log
     *
     * @param string $type
     * @param mixed $data
     */
    public function debug(string $type, $data): LogService;

    /**
     * @param string|int $prefix
     */
    public function addPrefix($prefix): LogService;

    /**
     * @param string|int $prefix
     * @return LogService
     */
    public function removePrefix($prefix): LogService;
}
