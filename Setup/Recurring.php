<?php

namespace TrueLayer\Connect\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Config\Model\ResourceModel\Config;
use TrueLayer\Connect\Api\Config\RepositoryInterface;
use TrueLayer\Connect\Api\Config\System\ConnectionInterface;
use Magento\Framework\Encryption\EncryptorInterface;

class Recurring implements InstallSchemaInterface
{
    public function __construct(private RepositoryInterface $configRepository, private EncryptorInterface $encryptor, private Config $resourceConfig)
    {
        
    }
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $path = ConnectionInterface::XML_PATH_CACHE_ENCRYPTION_KEY;
        $value = bin2hex(openssl_random_pseudo_bytes(32));
        $value = $this->encryptor->encrypt($value);
        $this->resourceConfig->saveConfig($path, $value, 'default', 0);

        $setup->endSetup();
    }
}