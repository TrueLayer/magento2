<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Webapi\WebhookInterface;
use TrueLayer\Connect\Service\Order\ProcessWebhook;

class Webhook implements WebhookInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var ProcessWebhook
     */
    private $processWebhook;
    /**
     * @var File
     */
    private $file;

    /**
     * Webhook constructor.
     *
     * @param LogRepository $logRepository
     * @param JsonSerializer $jsonSerializer
     * @param ProcessWebhook $processWebhook
     * @param File $file
     */
    public function __construct(
        LogRepository $logRepository,
        JsonSerializer $jsonSerializer,
        ProcessWebhook $processWebhook,
        File $file
    ) {
        $this->logRepository = $logRepository;
        $this->jsonSerializer = $jsonSerializer;
        $this->processWebhook = $processWebhook;
        $this->file = $file;
    }

    /**
     * @inheritDoc
     */
    public function processTransfer()
    {
        try {
            $post = $this->file->fileGetContents('php://input');
            $postArray = $this->jsonSerializer->unserialize($post);
            $this->logRepository->addDebugLog('webhook data', $postArray);
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Webhook processTransfer postData', $exception->getMessage());
            throw new LocalizedException(__('Post data should be provided.'));
        }

        if (isset($postArray['type']) && $postArray['type'] == 'payment_settled') {
            try {
                $this->processWebhook->execute($postArray['payment_id']);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Webhook processTransfer', $exception->getMessage());
                throw new LocalizedException(__($exception->getMessage()));
            }
        }
    }
}
