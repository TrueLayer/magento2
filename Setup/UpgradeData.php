<?php

namespace TrueLayer\Connect\Setup;

use Magento\Config\Model\ResourceModel\Config\Data\Collection as ConfigDataCollection;
use Magento\Config\Model\ResourceModel\Config as ConfigDataResourceModel;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use TrueLayer\Connect\Api\Config\System\ConnectionInterface;

class UpgradeData implements UpgradeDataInterface
{
	public function __construct(
		private ConfigDataCollection $dataCollection,
		private ConfigDataResourceModel $dataResourceModel,
		private Filesystem $filesystem,
		private File $file,
		private EncryptorInterface $encryptor,
	) {
	}

	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
	{
		$setupVersion = $context->getVersion();

		if(version_compare($setupVersion, '1.0.0', '<=')) {
			$this->encryptPrivateKeys();
		}
	}

	private function encryptPrivateKeys()
	{
		$this->dataCollection->addFilter('path', ConnectionInterface::XML_PATH_SANDBOX_PRIVATE_KEY, 'or');
		$this->dataCollection->addFilter('path', ConnectionInterface::XML_PATH_PRODUCTION_PRIVATE_KEY, 'or');
		$this->dataCollection->loadWithFilter();
		/** @var \Magento\Framework\App\Config\Value[] $configItems */
		$configItems = $this->dataCollection->getItems();
		$this->dataCollection->clear()->getSelect()->reset('where');

		$configPaths = [ConnectionInterface::XML_PATH_PRODUCTION_PRIVATE_KEY,ConnectionInterface::XML_PATH_SANDBOX_PRIVATE_KEY];
		$varDirectory = $this->filesystem->getDirectoryRead('var');
		foreach ($configItems as $configItem) {
			$configPath = $configItem->getPath();
			if (!in_array($configPath, $configPaths)) {
				continue;
			}
			$configValue = $configItem->getValue();
			if (!$configValue) {
				continue;
			}
			$isPath = str_starts_with($configValue, 'sandbox/') || str_starts_with($configValue, 'production/');
			$absPath = $isPath ? $varDirectory->getAbsolutePath('truelayer/' . $configValue) : null;
			$fileExists = $absPath ? $this->file->fileExists($absPath, true) : false;
			if ($fileExists) {
				$privateKey = $this->file->read($absPath, null);
				$encryptedKey = $this->encryptor->encrypt($privateKey);
				$configItem->setValue($encryptedKey);
				$this->file->rm($absPath);
			} else {
				$configItem->setValue(null);
			}
			$this->dataResourceModel->save($configItem);
		}
	}
}
