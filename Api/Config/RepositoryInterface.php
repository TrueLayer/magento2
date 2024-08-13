<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Config;

/**
 * Config repository interface
 * @api
 */
interface RepositoryInterface extends System\ConnectionInterface
{

    public const EXTENSION_CODE = 'TrueLayer_Connect';
    public const XML_PATH_EXTENSION_VERSION = 'payment/truelayer/version';
    public const XML_PATH_CURRENCIES = 'payment/truelayer/currency';

    /**
     * Get extension version
     *
     * @return string|null
     */
    public function getExtensionVersion(): ?string;

    /**
     * Get base url of the store
     *
     * @param int|null $storeId
     * @return string
     */
    public function getBaseUrl(int $storeId = null): string;

    /**
     * Get allowed currencies
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getCurrencies(int $storeId = null): array;
}
