<?php


namespace Truelayer\Connect\Service\Cache;

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\Cache\InvalidArgumentException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
