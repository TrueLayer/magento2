<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Log;

use TrueLayer\Connect\Api\Log\LogService as LogRepositoryInterface;

/**
 * Logs repository class
 */
class LogService implements LogRepositoryInterface
{
    /**
     * @var DebugLogger
     */
    private DebugLogger $debugLogger;

    /**
     * @var ErrorLogger
     */
    private ErrorLogger $errorLogger;

    /**
     * @var string
     */
    private string $prefix = '';

    /**
     * @param \TrueLayer\Connect\Service\Log\DebugLogger $debugLogger
     * @param \TrueLayer\Connect\Service\Log\ErrorLogger $errorLogger
     */
    public function __construct(
        DebugLogger $debugLogger,
        ErrorLogger $errorLogger
    ) {
        $this->debugLogger = $debugLogger;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @inheritDoc
     */
    public function error(string $type, $data = ''): self
    {
        $this->errorLogger->addLog("[$this->prefix $type]", $data);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function debug(string $type, $data = ''): self
    {
        $this->debugLogger->addLog("[$this->prefix $type]", $data);

        return $this;
    }

    /**
     * @inheriDoc
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }
}
