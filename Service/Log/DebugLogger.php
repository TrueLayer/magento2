<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Log;

use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * DebugLogger logger class
 */
class DebugLogger extends Logger
{
    /**
     * @var Json
     */
    private Json $json;

    /**
     * @var ConfigProvider
     */
    private ConfigProvider $configProvider;

    /**
     * DebugLogger constructor.
     *
     * @param Json $json
     * @param ConfigProvider $configProvider
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        Json $json,
        ConfigProvider $configProvider,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        $this->configProvider = $configProvider;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Add log entry
     *
     * @param string $type
     * @param mixed $data
     */
    public function addLog(string $type, $data): void
    {
        if (!$this->configProvider->isDebugLoggingEnabled()) {
            return;
        }

        $this->addRecord(static::INFO, $type . ': ' . $this->convertDataToString($data));
    }

    /**
     * @param $data
     * @return string
     */
    private function convertDataToString($data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if ($data instanceof \Exception) {
            return $data->getMessage() . " " . $data->getTraceAsString();
        }

        if (is_array($data) || is_object($data)) {
            return $this->json->serialize($data);
        }

        return 'failed to serialize error message';
    }
}
