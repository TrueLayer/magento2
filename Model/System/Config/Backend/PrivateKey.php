<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\System\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use TrueLayer\Connect\Api\Config\System\ConnectionInterface;

/**
 * Backend model for saving certificate file
 */
class PrivateKey extends Value
{
    public const FILENAME = 'private-key.pem';
    /**
     * @var File
     */
    private $file;
    /**
     * @var ReadInterface
     */
    private $tmpDirectory;
    /**
     * @var ReadInterface
     */
    private $varDirectory;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param Filesystem $filesystem
     * @param File $file
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Filesystem $filesystem,
        File $file,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->file = $file;
        $this->tmpDirectory = $filesystem->getDirectoryRead('sys_tmp');
        $this->varDirectory = $filesystem->getDirectoryRead('var');
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * Process additional data before save config.
     *
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave(): self
    {
        $value = (array)$this->getValue();
        $sandbox = $this->getPath() === ConnectionInterface::XML_PATH_SANDBOX_PRIVATE_KEY;
        $directory = $this->getDirectory($sandbox);

        if (!empty($value['delete'])) {
            $this->deleteCertificateAndReset($this->isObjectNew() ? '' : $this->getOldValue());
            return $this;
        }

        $tmpName = $this->getTmpName($sandbox);
        $isUploading = (is_string($tmpName) && !empty($tmpName) && $this->tmpDirectory->isExist($tmpName));
        
        if (!$isUploading) {
            $this->setValue($this->isObjectNew() ? '' : $this->getOldValue());
            return $this;
        }
        
        if ($isUploading) {
            $tmpPath = $this->tmpDirectory->getAbsolutePath($tmpName);
            if (!$this->tmpDirectory->stat($tmpPath)['size']) {
                throw new LocalizedException(__('The TrueLayer certificate file is empty.'));
            }

            $destinationPath = $this->varDirectory->getAbsolutePath('truelayer/' . $directory);

            $filePath = $directory . self::FILENAME;
            $this->file->checkAndCreateFolder($destinationPath);
            $this->file->mv(
                $tmpPath,
                $this->varDirectory->getAbsolutePath('truelayer/' . $filePath)
            );
            $this->setValue($filePath);
        }

        return $this;
    }

    /**
     * Delete the cert file from disk when deleting the setting.
     *
     * @return $this
     */
    public function beforeDelete()
    {
        $returnValue = parent::beforeDelete();
        $filePath = $this->isObjectNew() ? '' : $this->getOldValue();
        if ($filePath) {
            $absolutePath = $this->varDirectory->getAbsolutePath('truelayer/' . $filePath);
            if ($this->file->fileExists($absolutePath)) {
                $this->file->rm($absolutePath);
            }
        }
        return $returnValue;
    }

    /**
     * Delete the cert file and unset the config value.
     *
     * @param string $filePath
     * @return void
     */
    private function deleteCertificateAndReset(string $filePath): void
    {
        if (!empty($filePath)) {
            $absolutePath = $this->varDirectory->getAbsolutePath('truelayer/' . $filePath);
            if ($this->file->fileExists($absolutePath)) {
                $this->file->rm($absolutePath);
            }
        }

        $this->setValue('');
    }

    /**
     * Returns the directory based on set scope.
     *
     * @param bool $sandbox
     * @return string
     */
    private function getDirectory(bool $sandbox): string
    {
        $mode = $sandbox ? 'sandbox' : 'production';
        return $this->getScope() !== 'default'
            ? sprintf('%s/%s/%s/', $mode, $this->getScope(), $this->getScopeId())
            : sprintf('%s/default/', $mode);
    }

    /**
     * Returns the path to the uploaded tmp_file based on set scope.
     *
     * @param bool $sandbox
     * @return string
     */
    private function getTmpName(bool $sandbox): ?string
    {
        $files = $_FILES;
        if (empty($files)) {
            return null;
        }
        try {
            $tmpName = $files['groups']['tmp_name']['general']['fields'][$sandbox ? 'sandbox_private_key' : 'production_private_key']['value'];
            return empty($tmpName) ? null : $tmpName;
        } catch (\Exception $e) {
            return null;
        }
    }
}
