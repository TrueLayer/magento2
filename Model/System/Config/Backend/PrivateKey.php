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

        if (!empty($value['delete']) && !empty($value['value'])) {
            $this->deleteCertificateAndReset($value['value']);
            return $this;
        }

        if (!empty($value['value'])) {
            $this->setValue($value['value']);
        }

        if (empty($value['tmp_name'])) {
            return $this;
        }

        $tmpPath = $this->tmpDirectory->getAbsolutePath($value['tmp_name']);
        if ($tmpPath && $this->tmpDirectory->isExist($tmpPath)) {
            if (!$this->tmpDirectory->stat($tmpPath)['size']) {
                throw new LocalizedException(__('The TrueLayer certificate file is empty.'));
            }

            $filePath = $this->getFilePath($sandbox);
            $destinationPath = $this->varDirectory->getAbsolutePath('truelayer/' . $filePath);

            $this->file->checkAndCreateFolder($destinationPath);
            $this->file->mv(
                $tmpPath,
                $this->varDirectory->getAbsolutePath('truelayer/' . $filePath . $value['name'])
            );
            $this->setValue($filePath . $value['name']);
        }

        return $this;
    }

    /**
     * Delete the cert file and unset the config value.
     *
     * @param string $filePath
     * @return void
     */
    private function deleteCertificateAndReset(string $filePath): void
    {
        $absolutePath = $this->varDirectory->getAbsolutePath('truelayer/' . $filePath);
        if ($this->file->fileExists($absolutePath)) {
            $this->file->rm($absolutePath);
        }

        $this->setValue('');
    }

    /**
     * Returns the filepath based on set scope.
     *
     * @param bool $sandbox
     * @return string
     */
    private function getFilePath(bool $sandbox): string
    {
        $mode = $sandbox ? 'sandbox' : 'production';
        return $this->getScope() !== 'default'
            ? sprintf('%s/%s/%s/', $mode, $this->getScope(), $this->getScopeId())
            : sprintf('%s/default/', $mode);
    }
}
