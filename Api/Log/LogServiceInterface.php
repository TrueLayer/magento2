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
interface LogServiceInterface
{
    /**
     * Add record to error log
     *
     * @param string $type
     * @param mixed $data
     */
    public function error(string $type, $data = ''): LogServiceInterface;

    /**
     * Add record to debug log
     *
     * @param string $type
     * @param mixed $data
     */
    public function debug(string $type, $data = ''): LogServiceInterface;

    /**
     * @param string|int $prefix
     */
    public function addPrefix($prefix): LogServiceInterface;

    /**
     * @param string|int $prefix
     * @return LogServiceInterface
     */
    public function removePrefix($prefix): LogServiceInterface;
}
