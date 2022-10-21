<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Logger;

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
    private $json;
    /**
     * @var ConfigProvider
     */
    private $configProvider;

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
     * Add debug data to truelayer Log
     *
     * @param string $type
     * @param mixed $data
     */
    public function addLog(string $type, $data): void
    {
        if (!$this->configProvider->isDebugLoggingEnabled()) {
            return;
        }

        if (is_array($data) || is_object($data)) {
            $this->addRecord(static::INFO, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::INFO, $type . ': ' . $data);
        }
    }
}
