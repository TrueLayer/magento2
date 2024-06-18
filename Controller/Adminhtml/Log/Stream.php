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
    private const READ_MAX_CHUNKS = 1024;
    private const READ_BUFFER_SIZE = 1024;

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
        $size = $this->file->stat($logFilePath)['size'];
        if (!$size) {
            return [];
        }
        $file = $this->file->fileOpen($logFilePath, 'r');

        // we will start reading the file at the end
        $position = $size;
        $bufferSize = self::READ_BUFFER_SIZE;

        $readCounter = 0;
        $lineCounter = 0;

        $logLines = [];
        $logLine = '';
        try {
            do {
                $position -= $bufferSize;
                if ($position < 0) {
                    $bufferSize = $bufferSize + $position;
                    $position = 0;
                }
                $seek = fseek($file, $position);
                if ($seek === -1) {
                    break;
                }
                $readBuffer = fread($file, $bufferSize);
                $readCounter++;
                $bufferLines = explode("\n", $readBuffer);
                $lastLineKey = count($bufferLines) -1;
                foreach (array_reverse($bufferLines) as $key => $bufferLine) {
                    $bufferLine = str_replace("\r", "", $bufferLine); //remove CR byte if present
                    $logLine = $bufferLine . $logLine;
                    if ('' !== $logLine // ignore empty lines
                        && (
                            $key !== $lastLineKey // The last line may be incomplete, we need to keep prepending it until we hit another newline
                            || ($key === $lastLineKey && $position === 0) // Unless we reached the beginning of the file
                            )
                    ) {
                        $logLines[] = $this->formatLine($logLine);
                        $logLine = '';
                        $lineCounter++;
                        if ($lineCounter == self::MAX_LINES) {
                            break 2;
                        }
                    }
                }
            }
            /**
             * We have not collected MAX_LINES amount of lines yet but
             * either we reached the beginning of the file
             * or we have read READ_BUFFER_SIZE * READ_MAX_CHUNKS bytes from the file already.
             */
            while ($position > 0 && $readCounter < self::READ_MAX_CHUNKS);
        } catch (\Exception $e) {}
        finally {
            if ($file) {
                $this->file->fileClose($file);
            }
        }

        $logLines = array_reverse($logLines);

        return $logLines;
    }

    private function formatLine($line) {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $data = explode('] ', $line);
        $date = ltrim(array_shift($data), '[');
        $data = implode('] ', $data);
        $data = explode(': ', $data);
        array_shift($data);
        return [
            'date' => $date,
            'msg' => implode(': ', $data)
        ];
    }
}
