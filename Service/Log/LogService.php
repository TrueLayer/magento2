<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Log;

use Exception;
use Monolog\Logger;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigProvider;
use TrueLayer\Connect\Api\Log\LogService as LogServiceInterface;

/**
 * Logs repository class
 */
class LogService implements LogServiceInterface
{
    private ConfigProvider $configProvider;
    private Logger $debugLogger;
    private Logger $errorLogger;
    private array $prefixes = [];

    /**
     * @param ConfigProvider $configProvider
     * @param Logger $debugLogger
     * @param Logger $errorLogger
     */
    public function __construct(
        ConfigProvider $configProvider,
        Logger $debugLogger,
        Logger $errorLogger
    ) {
        $this->configProvider = $configProvider;
        $this->debugLogger = $debugLogger;
        $this->errorLogger = $errorLogger;
    }

    /**
     * @inheritDoc
     */
    public function error(string $type, $data = ''): self
    {
        $this->errorLogger->addRecord(Logger::ERROR, $this->buildMessage($type, $data));

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function debug(string $type, $data = ''): self
    {
        if ($this->configProvider->isDebugLoggingEnabled()) {
            $this->debugLogger->addRecord(Logger::INFO, $this->buildMessage($type, $data));
        }

        return $this;
    }

    /**
     * @inheriDoc
     */
    public function addPrefix($prefix): self
    {
        $this->prefixes[] = $prefix;
        return $this;
    }

    /**
     * @param int|string $prefix
     * @return $this
     */
    public function removePrefix($prefix): LogService
    {
        foreach ($this->prefixes as $key => $value) {
            if ($value ===$prefix) {
                unset($this->prefixes[$key]);
                return $this;
            }
        }
        return $this;
    }

    /**
     * @param string $msg
     * @param mixed $data
     * @return string
     */
    private function buildMessage(string $msg, $data = ''): string
    {
        $parts = $this->prefixes;
        $parts[] = $msg;
        
        if ($serialisedData = $this->convertDataToString($data)) {
            $parts[] = $serialisedData;
        }

        return join(' > ', $parts);
    }

    /**
     * @param $data
     * @return string
     */
    private function convertDataToString($data): string
    {
        if ($data instanceof Exception) {
            return $data->getMessage() . " " . $data->getTraceAsString();
        }

        if (empty($data)) {
            return '';
        }

        if (is_array($data) || is_object($data)) {
            if ($result = json_encode($data)) {
                return $result;
            }
        }

        return "$data";
    }
}
