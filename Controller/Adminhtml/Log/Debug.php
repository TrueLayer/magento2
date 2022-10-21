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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

/**
 * AJAX controller to check debug log
 */
class Debug extends Action implements HttpPostActionInterface
{

    /**
     * Debug log file path pattern
     */
    public const DEBUG_LOG_FILE = '%s/log/truelayer/debug.log';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var DirectoryList
     */
    private $dir;
    /**
     * @var File
     */
    private $file;

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
        if ($this->isLogExists()) {
            $result = ['result' => $this->prepareLogText()];
        } else {
            $result = __('Log is empty');
        }
        return $resultJson->setData($result);
    }

    /**
     * Check is log file exists
     *
     * @return bool
     */
    private function isLogExists(): bool
    {
        try {
            $logFile = sprintf(self::DEBUG_LOG_FILE, $this->dir->getPath('var'));
            return $this->file->isExists($logFile);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Prepare encoded log text
     *
     * @return array
     * @throws FileSystemException
     */
    private function prepareLogText(): array
    {
        $logFile = sprintf(self::DEBUG_LOG_FILE, $this->dir->getPath('var'));
        $fileContent = explode(PHP_EOL, $this->file->fileGetContents($logFile));
        if (count($fileContent) > 100) {
            $fileContent = array_slice($fileContent, -100, 100, true);
        }
        $result = [];
        foreach ($fileContent as $line) {
            $data = explode('] ', $line);
            $date = ltrim(array_shift($data), '[');
            $data = implode('] ', $data);
            $data = explode(': ', $data);
            array_shift($data);
            $result[] = [
                'date' => $date,
                'msg' => implode(': ', $data)
            ];
        }
        return $result;
    }
}
