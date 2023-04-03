<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Adminhtml\Credentials;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Service\Api\GetClient;

/**
 * Credential check controller to validate API data
 */
class Check extends Action implements HttpPostActionInterface
{

    /**
     * @var GetClient
     */
    private $getClient;
    /**
     * @var Json
     */
    private $resultJson;
    /**
     * @var ConfigRepository
     */
    private $configProvider;

    /**
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GetClient $getClient
     * @param ConfigRepository $configProvider
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        GetClient $getClient,
        ConfigRepository $configProvider
    ) {
        $this->getClient = $getClient;
        $this->resultJson = $resultJsonFactory->create();
        $this->configProvider = $configProvider;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        $config = $this->getCredentials();
        if (!$config['credentials']['client_id'] || !$config['credentials']['client_secret']) {
            return $this->resultJson->setData(
                [
                    'success' => true,
                    'msg' => __('Set credentials first')
                ]
            );
        }

        try {
            $result = $this->getClient->execute(
                (int)$config['store_id'],
                ['credentials' => $config['credentials']]
            );
        } catch (\Exception $exception) {
            return $this->resultJson->setData(
                ['success' => false, 'msg' => $exception->getMessage()]
            );
        }
        if (!$result) {
            return $this->resultJson->setData(
                ['success' => false, 'msg' => 'Credentials are not correct']
            );
        }
        try {
            $result->getMerchantAccounts();
            return $this->resultJson->setData(
                [
                    'success' => true,
                    'msg' => __('Credentials correct!')->render()
                ]
            );
        } catch (\Exception $exception) {
            return $this->resultJson->setData(
                ['success' => false, 'msg' => 'Credentials are not correct']
            );
        }
    }

    /**
     * @return array
     */
    private function getCredentials(): array
    {
        $storeId = (int)$this->getRequest()->getParam('store');
        $mode = $this->getRequest()->getParam('mode');
        if ($mode == 'sandbox') {
            $clientId = $this->getRequest()->getParam('sandbox_client_id');
            $clientSecret = $this->getRequest()->getParam('sandbox_client_secret');
            $keyId = $this->getRequest()->getParam('sandbox_key_id');
        } else {
            $clientId = $this->getRequest()->getParam('production_client_id');
            $clientSecret = $this->getRequest()->getParam('production_client_secret');
            $keyId = $this->getRequest()->getParam('production_key_id');
        }

        $configCredentials = $this->configProvider->getCredentials($storeId);
        if ($clientSecret == '******') {
            $clientSecret = $configCredentials['client_secret'];
        }

        return [
            'store_id' => $storeId,
            'credentials' => [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'private_key' => $configCredentials['private_key'],
                'key_id' => $keyId
            ]
        ];
    }
}
