<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Config\System;

use Magento\Config\Model\ResourceModel\Config as ResourceConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Base PaymentTransactionRepository provider class
 */
class BaseRepository
{

    /**
     * @var Json
     */
    protected $json;
    /**
     * @var ResourceConfig
     */
    protected $resourceConfig;
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * BaseRepository constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     * @param ResourceConfig $resourceConfig
     * @param EncryptorInterface $encryptor
     * @param TypeListInterface $cacheTypeList
     * @param StoreManagerInterface $storeManager
     * @param DirectoryList $directoryList
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Json $json,
        ResourceConfig $resourceConfig,
        EncryptorInterface $encryptor,
        TypeListInterface $cacheTypeList,
        StoreManagerInterface $storeManager,
        DirectoryList $directoryList
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->json = $json;
        $this->resourceConfig = $resourceConfig;
        $this->encryptor = $encryptor;
        $this->cacheTypeList = $cacheTypeList;
        $this->storeManager = $storeManager;
        $this->directoryList = $directoryList;
    }

    /**
     * Retrieve config value array by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return array
     */
    protected function getStoreValueArray(string $path, ?int $storeId = null, ?string $scope = null): array
    {
        $value = $this->getStoreValue($path, (int)$storeId, $scope);

        if (empty($value)) {
            return [];
        }

        try {
            return $this->json->unserialize($value);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Retrieve config value by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return string|null
     */
    protected function getStoreValue(string $path, ?int $storeId = null, ?string $scope = null): ?string
    {
        if (!$scope) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->getValue($path, $scope, $storeId);
    }

    /**
     * Set Config data
     *
     * @param string $value
     * @param string $key
     * @param int|null $storeId
     * @return void
     */
    protected function setConfigData(string $value, string $key, ?int $storeId = null): void
    {
        if ($storeId) {
            $this->resourceConfig->saveConfig($key, $value, 'stores', (int)$storeId);
        } else {
            $this->resourceConfig->saveConfig($key, $value, 'default', 0);
        }
    }

    /**
     * Retrieve config flag by path, storeId and scope
     *
     * @param string $path
     * @param int|null $storeId
     * @param string|null $scope
     * @return bool
     */
    protected function isSetFlag(string $path, ?int $storeId = null, ?string $scope = null): bool
    {
        if (empty($scope)) {
            $scope = ScopeInterface::SCOPE_STORE;
        }

        return $this->scopeConfig->isSetFlag($path, $scope, $storeId);
    }
}
