<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Api;

use GuzzleHttp\Client as GuzzleClient;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Client;
use TrueLayer\Interfaces\Client\ClientInterface;

/**
 * TrueLayer API adapter wrapper
 */
class GetClient
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
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * Adapter constructor.
     *
     * @param ConfigRepository $configProvider
     * @param LogRepository $logRepository
     * @param GuzzleClient $guzzleClient
     */
    public function __construct(
        ConfigRepository $configProvider,
        LogRepository $logRepository,
        GuzzleClient $guzzleClient
    ) {
        $this->configProvider = $configProvider;
        $this->logRepository = $logRepository;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param int $storeId
     * @param array|null $data
     * @return ClientInterface|null
     */
    public function execute(int $storeId = 0, ?array $data = []): ?ClientInterface
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
     */
    private function getClient(): ?ClientInterface
    {
        try {
            $client = Client::configure()
                ->clientId($this->credentials['client_id'])
                ->clientSecret($this->credentials['client_secret'])
                ->keyId($this->configProvider->getKid($this->storeId))
                ->pemFile($this->configProvider->getPathToPrivateKey($this->storeId))
                ->httpClient($this->guzzleClient)
                ->useProduction(!$this->configProvider->isSandbox());

            return $client->create();
        } catch (\Exception $e) {
            $this->logRepository->addDebugLog('Get Client', $e->getMessage());
            return null;
        }
    }
}
