<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config;

use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepositoryInterface;

/**
 * Config repository class
 */
class Repository extends System\ConnectionRepository implements ConfigRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getExtensionVersion(): string
    {
        return preg_replace(
            "/[^0-9.]/",
            '',
            (string)$this->getStoreValue(self::XML_PATH_EXTENSION_VERSION)
        );
    }

    /**
     * @inheritDoc
     */
    public function getBaseUrl(int $storeId = null): string
    {
        try {
            return $this->storeManager->getStore($storeId)->getBaseUrl();
        } catch (\Exception $exception) {
            return '';
        }
    }

    /**
     * @param int|null $storeId
     * @return string[]
     */
    public function getCurrencies(int $storeId = null): array
    {
        try {
            return explode(',', $this->getStoreValue(self::XML_PATH_CURRENCIES));
        } catch (\Exception $exception) {
            return [];
        }
    }
}
