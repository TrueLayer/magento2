<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Log;

use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * ErrorLogger logger class
 */
class ErrorLogger extends Logger
{

    /**
     * @var Json
     */
    private $json;

    /**
     * ErrorLogger constructor.
     *
     * @param Json $json
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        Json $json,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Add error data to truelayer Log
     *
     * @param string $type
     * @param mixed $data
     *
     */
    public function addLog(string $type, $data): void
    {
        if (is_array($data) || is_object($data)) {
            $this->addRecord(static::ERROR, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::ERROR, $type . ': ' . $data);
        }
    }
}
