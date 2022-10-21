<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\System;

use TrueLayer\Connect\Api\Config\System\ConnectionInterface;
use TrueLayer\Connect\Model\Config\Source\Mode;

/**
 * Credentials provider class
 */
class ConnectionRepository extends DebugRepository implements ConnectionInterface
{

    /**
     * @inheritDoc
     */
    public function getMerchantAccountId(?int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_MERCHANT_ACCOUNT_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function isSandbox(?int $storeId = null): bool
    {
        return $this->getStoreValue(self::XML_PATH_MODE, $storeId) == Mode::SANDBOX;
    }

    /**
     * @inheritDoc
     */
    public function getCredentials(?int $storeId = null, ?bool $forceSandbox = null): array
    {
        $isSandBox = $forceSandbox === null ? $this->isSandbox($storeId) : $forceSandbox;
        return [
            "client_id" => $this->getClientId($storeId, $isSandBox),
            "client_secret" => $this->getClientSecret($storeId, $isSandBox),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPathToPrivateKey(?int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_PATH_TO_PRIVATE_KEY, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getKid(?int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_KID, $storeId);
    }

    /**
     * @param int|null $storeId
     * @param false $isSandBox
     *
     * @return string
     */
    private function getClientId(?int $storeId = null, bool $isSandBox = false): string
    {
        $path = $isSandBox ? self::XML_PATH_SANDBOX_CLIENT_ID : self::XML_PATH_PRODUCTION_CLIENT_ID;
        return (string)$this->getStoreValue($path, $storeId);
    }

    /**
     * @param int|null $storeId
     * @param bool $isSandBox
     *
     * @return string
     */
    private function getClientSecret(?int $storeId = null, bool $isSandBox = false): string
    {
        $path = $isSandBox ? self::XML_PATH_SANDBOX_CLIENT_SECRET : self::XML_PATH_PRODUCTION_CLIENT_SECRET;
        if ($value = $this->getStoreValue($path, $storeId)) {
            return $this->encryptor->decrypt($value);
        }

        return '';
    }
}
