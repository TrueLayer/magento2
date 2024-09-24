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
    public function getMerchantAccountName(?int $storeId = null): string
    {
        return (string)$this->getStoreValue(self::XML_PATH_MERCHANT_ACCOUNT_NAME, $storeId);
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
            "private_key" => $this->getPathToPrivateKey($storeId, $isSandBox),
            "key_id" => $this->getKeyId($storeId, $isSandBox)
        ];
    }

    /**
     * @param int|null $storeId
     * @param bool $isSandBox
     * @return string
     */
    private function getPathToPrivateKey(?int $storeId = null, bool $isSandBox = false): string
    {
        $path = $isSandBox ? self::XML_PATH_SANDBOX_PRIVATE_KEY : self::XML_PATH_PRODUCTION_PRIVATE_KEY;
        if (!$savedPrivateKey = $this->getStoreValue($path, $storeId)) {
            return '';
        }

        try {
            return $this->directoryList->getPath('var') . '/truelayer/' . $savedPrivateKey;
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param int|null $storeId
     * @param bool $isSandBox
     * @return string
     */
    private function getKeyId(?int $storeId = null, bool $isSandBox = false): string
    {
        $path = $isSandBox ? self::XML_PATH_SANDBOX_KEY_ID : self::XML_PATH_PRODUCTION_KEY_ID;
        return (string)$this->getStoreValue($path, $storeId);
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
