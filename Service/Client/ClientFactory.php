<?php

/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Client;

use TrueLayer\Client;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\LogService as LogRepository;
use TrueLayer\Interfaces\Client\ClientInterface;

/**
 * TrueLayer Client Factory
 */
class ClientFactory
{
    /**
     * @var int
     */
    private $storeId = 0;
    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var array
     */
    private $credentials;

    /**
     * @param ConfigRepository $configProvider
     * @param LogRepository $logRepository
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogRepository $logRepository
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
    }

    /**
     * @param int $storeId
     * @param array|null $data
     * @return ClientInterface|null
     */
    public function create(int $storeId = 0, ?array $data = []): ?ClientInterface
    {
        $this->storeId = $storeId;
        if (isset($data['credentials'])) {
            $this->credentials = $data['credentials'];
        } else {
            $this->credentials = $this->configProvider->getCredentials((int)$storeId);
        }

        return $this->getClient();
    }

    /**
     * @return ClientInterface|null
     * @throws \TrueLayer\Exceptions\SignerException
     */
    private function getClient(): ?ClientInterface
    {
        try {
            \TrueLayer\Settings::tlAgent('truelayer-magento/' . $this->configProvider->getExtensionVersion());
            $client = Client::configure()
                ->clientId($this->credentials['client_id'])
                ->clientSecret($this->credentials['client_secret'])
                ->keyId($this->credentials['key_id'])
                ->pemFile($this->credentials['private_key'])
                ->useProduction(!$this->configProvider->isSandbox());

            return $client->create();
        } catch (\Exception $e) {
            $this->logRepository->debug('Get Client', $e->getMessage());
            throw $e;
        }
    }
}
