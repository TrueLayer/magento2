<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

/**
 * AJAX controller to check logs
 */
class Stream extends Action implements HttpPostActionInterface
{
    /**
     * Error log file path pattern
     */
    public const LOG_FILE = '%s/log/truelayer/%s.log';
    /**
     * Limit stream size to 100 lines
     */
    public const MAX_LINES = 100;

    private JsonFactory $resultJsonFactory;
    private DirectoryList $dir;
    private File $file;
    private RequestInterface $request;

    /**
     * Check constructor.
     *
     * @param Action\Context $context
     * @param JsonFactory $resultJsonFactory
     * @param DirectoryList $dir
     * @param File $file
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        DirectoryList $dir,
        File $file
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $context->getRequest();
        $this->dir = $dir;
        $this->file = $file;
        parent::__construct($context);
    }

    /**
     * @return Json
     * @throws FileSystemException
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $logFilePath = $this->getLogFilePath();

        if ($logFilePath && $this->isLogExists($logFilePath)) {
            $result = ['result' => $this->prepareLogText($logFilePath)];
        } else {
            $result = __('Log is empty');
        }

        return $resultJson->setData($result);
    }

    /**
     * @return string
     */
    private function getLogFilePath(): ?string
    {
        try {
            $type = $this->request->getParam('type') == 'error' ? 'error' : 'debug';
            return sprintf(self::LOG_FILE, $this->dir->getPath('var'), $type);
        } catch (\Exception $exception) {
            return null;
        }
    }

    /**
     * Check is log file exists
     *
     * @param $logFilePath
     * @return bool
     */
    private function isLogExists($logFilePath): bool
    {
        try {
            return $this->file->isExists($logFilePath);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param $logFilePath
     * @return array
     * @throws FileSystemException
     */
    private function prepareLogText($logFilePath): array
    {
        $file = $this->file->fileOpen($logFilePath, 'r');
        $count = 0;

        $result = [];
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = fgets($file)) !== false && $count < self::MAX_LINES) {
            $data = explode('] ', $line);
            $date = ltrim(array_shift($data), '[');
            $data = implode('] ', $data);
            $data = explode(': ', $data);
            array_shift($data);
            $result[] = [
                'date' => $date,
                'msg' => implode(': ', $data)
            ];
            $count++;
        }

        $this->file->fileClose($file);
        return $result;
    }
}
