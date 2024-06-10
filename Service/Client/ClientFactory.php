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
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Exceptions\SignerException;
use TrueLayer\Interfaces\Client\ClientInterface;
use TrueLayer\Settings;

class ClientFactory
{
    private ConfigRepository $configProvider;
    private LogService $logger;

    /**
     * @param ConfigRepository $configProvider
     * @param LogService $logger
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogService $logger
    ) {
        $this->configProvider = $configProvider;
        $this->logger = $logger->prefix('ClientFactory');
    }

    /**
     * @param int $storeId
     * @param array|null $data
     * @return ClientInterface|null
     * @throws SignerException
     */
    public function create(int $storeId = 0, ?array $data = []): ?ClientInterface
    {
        $credentials = $data['credentials'] ?? $this->configProvider->getCredentials($storeId);

        try {
            return $this->createClient($credentials);
        } catch (Exception $e) {
            $this->logger->debug('Create Fail', $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array $credentials
     * @return ClientInterface|null
     * @throws SignerException
     */
    private function createClient(array $credentials): ?ClientInterface
    {
        Settings::tlAgent('truelayer-magento/' . $this->configProvider->getExtensionVersion());
        return Client::configure()
            ->clientId($credentials['client_id'])
            ->clientSecret($credentials['client_secret'])
            ->keyId($credentials['key_id'])
            ->pemFile($credentials['private_key'])
            ->useProduction(!$this->configProvider->isSandbox())
            ->create();
    }
}
