<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Config\System;

/**
 * Connection group config repository interface
 * @api
 */
interface ConnectionInterface extends DebugInterface
{

    /** Connection Group */
    public const XML_PATH_MODE = 'payment/truelayer/mode';
    public const XML_PATH_MERCHANT_ACCOUNT_ID = 'payment/truelayer/merchant_account_id';
    public const XML_PATH_SANDBOX_CLIENT_ID = 'payment/truelayer/sandbox_client_id';
    public const XML_PATH_SANDBOX_CLIENT_SECRET = 'payment/truelayer/sandbox_client_secret';
    public const XML_PATH_PRODUCTION_CLIENT_ID = 'payment/truelayer/production_client_id';
    public const XML_PATH_PRODUCTION_CLIENT_SECRET = 'payment/truelayer/production_client_secret';
    public const XML_PATH_PATH_TO_PRIVATE_KEY = 'payment/truelayer/path_to_private_key';
    public const XML_PATH_KID = 'payment/truelayer/kid';

    /**
     * Get Merchant Account ID
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getMerchantAccountId(?int $storeId = null): string;

    /**
     * Flag for sandbox mode
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isSandbox(?int $storeId = null): bool;

    /**
     * Get associated array of credentials
     *
     * @param int|null  $storeId
     * @param bool|null $forceSandbox
     *
     * @return array
     */
    public function getCredentials(?int $storeId = null, ?bool $forceSandbox = null): array;

    /**
     * Get path to private key
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getPathToPrivateKey(?int $storeId = null): string;

    /**
     * Get kid
     *
     * @param int|null $storeId
     *
     * @return string
     */
    public function getKid(?int $storeId = null): string;
}
