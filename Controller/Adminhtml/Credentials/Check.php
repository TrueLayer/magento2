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
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Service\Api\GetClient;
use TrueLayer\Interfaces\Client\ClientInterface;

/**
 * Credential check controller to validate API data
 */
class Check extends Action implements HttpPostActionInterface
{

    private const PEM_UPLOAD_FILE = '/truelayer/temp/private_key.pem';
    /**
     * @var DirectoryList
     */
    private $directoryList;
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
     * @var File
     */
    private $file;

    /**
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param GetClient $getClient
     * @param ConfigRepository $configProvider
     * @param File $file
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        GetClient $getClient,
        ConfigRepository $configProvider,
        File $file,
        DirectoryList $directoryList
    ) {
        $this->getClient = $getClient;
        $this->resultJson = $resultJsonFactory->create();
        $this->configProvider = $configProvider;
        $this->file = $file;
        $this->directoryList = $directoryList;
        parent::__construct($context);
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        try {
            $this->testCredentials()->getMerchantAccounts();
            return $this->resultJson->setData(
                ['success' => true, 'msg' => __('Credentials correct!')->render()]
            );
        } catch (\Exception $exception) {
            return $this->resultJson->setData(
                ['success' => false, 'msg' => 'Credentials are not correct']
            );
        }
    }

    /**
     * @throws LocalizedException
     * @throws FileSystemException
     */
    private function testCredentials(): ?ClientInterface
    {
        $config = $this->getCredentials();

        if (!$config['credentials']['client_id']) {
            throw new LocalizedException(__('No Client ID set!'));
        }

        if (!$config['credentials']['client_secret']) {
            throw new LocalizedException(__('No Client Secret set!'));
        }

        $result = $this->getClient->execute(
            (int)$config['store_id'],
            ['credentials' => $config['credentials']]
        );

        $this->cleanSavedTemporaryPrivateKey();

        if (!$result) {
            throw new LocalizedException(__('Credentials are not correct.'));
        }

        return $result;
    }

    /**
     * @return array
     * @throws FileSystemException
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
                'private_key' => $this->getPrivateKeyPath($configCredentials),
                'key_id' => $keyId
            ]
        ];
    }

    /**
     * @param array $configCredentials
     * @return string
     * @throws FileSystemException
     */
    private function getPrivateKeyPath(array $configCredentials): string
    {
        if ($privateKey = $this->getRequest()->getParam('private_key')) {
            $path = $this->directoryList->getPath('var') . self::PEM_UPLOAD_FILE;
            $fileInfo = $this->file->getPathInfo($path);

            if (!$this->file->fileExists($fileInfo['dirname'])) {
                $this->file->mkdir($fileInfo['dirname']);
            }

            $this->file->write($path, $privateKey);

            return $path;
        }

        return $configCredentials['private_key'];
    }

    /**
     * @return void
     * @throws FileSystemException
     */
    private function cleanSavedTemporaryPrivateKey(): void
    {
        $path = $this->directoryList->getPath('var') . self::PEM_UPLOAD_FILE;
        if ($this->file->fileExists($path)) {
            $this->file->rm($path);
        }
    }
}
