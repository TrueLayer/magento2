<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Service\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory as GuzzleClient;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;

/**
 * TrueLayer GuzzleHttp adapter wrapper
 */
class GetGuzzleClient
{

    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var GuzzleClient
     */
    private $guzzleClient;

    /**
     * Adapter constructor.
     *
     * @param ConfigRepository $configProvider
     * @param GuzzleClient $guzzleClient
     */
    public function __construct(
        ConfigRepository $configProvider,
        GuzzleClient $guzzleClient
    ) {
        $this->configProvider = $configProvider;
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @return Client
     */
    public function execute()
    {
        return $this->guzzleClient->create(
            ['TL-Agent' => 'truelayer-magento/' . $this->configProvider->getExtensionVersion()]
        );
    }
}
