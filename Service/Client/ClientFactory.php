<?php

/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Client;

use Exception;
use TrueLayer\Client;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Service\Cache\Psr16CacheAdapter;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;
use TrueLayer\Interfaces\Client\ClientInterface;
use TrueLayer\Settings;

class ClientFactory
{
    private ConfigRepository $configProvider;
    private LogServiceInterface $logger;
    private Psr16CacheAdapter $cacheAdapter;

    /**
     * @param ConfigRepository $configProvider
     * @param LogServiceInterface $logger
     * @param Psr16CacheAdapter $cacheAdapter
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogServiceInterface $logger,
        Psr16CacheAdapter $cacheAdapter,
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * @param int $storeId
     * @param array|null $data
     * @return ClientInterface|null
     * @throws SignerException|InvalidArgumentException
     */
    public function create(int $storeId = 0, ?array $data = []): ?ClientInterface
    {
        $forceSandbox = $data['force_sandbox'] ?? null;
        $credentials = $data['credentials'] ?? $this->configProvider->getCredentials($storeId, $forceSandbox);

        try {
            return $this->createClient($credentials, $forceSandbox);
        } catch (Exception $e) {
            $this->logger->debug('Client Creation Failed', $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array $credentials
     * @param bool|null $forceSandbox
     * @return ClientInterface|null
     * @throws InvalidArgumentException
     * @throws SignerException
     */
    private function createClient(array $credentials, ?bool $forceSandbox = null): ?ClientInterface
    {
        Settings::tlAgent('truelayer-magento/' . $this->configProvider->getExtensionVersion());

        $cacheEncryptionKey = $credentials['cache_encryption_key'];

        $clientFactory = Client::configure();
        $clientFactory->clientId($credentials['client_id'])
            ->clientSecret($credentials['client_secret'])
            ->keyId($credentials['key_id'])
            ->pemFile($credentials['private_key'])
            ->useProduction(is_null($forceSandbox) ? !$this->configProvider->isSandbox() : !$forceSandbox);

        if ($cacheEncryptionKey) {
            $clientFactory->cache($this->cacheAdapter, $cacheEncryptionKey);
        }

        return $clientFactory->create();
    }
}
