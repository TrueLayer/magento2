<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Store\Model\App\Emulation;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\RepositoryInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\RepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\Webapi\WebhookInterface;
use TrueLayer\Connect\Service\Order\ProcessWebhook;
use TrueLayer\Exceptions\Exception;
use TrueLayer\Interfaces\Webhook as TrueLayerWebhookInterface;
use TrueLayer\Webhook as TrueLayerWebhook;

/**
 * Class Webhook
 */
class Webhook implements WebhookInterface
{

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ProcessWebhook
     */
    private $processWebhook;
    /**
     * @var ConfigRepository
     */
    private $configProvider;
    /**
     * @var JsonSerializer
     */
    private $jsonSerializer;
    /**
     * @var File
     */
    private $file;
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var int|null
     */
    private $storeId = null;

    /**
     * Webhook constructor.
     *
     * @param LogRepository $logRepository
     * @param ProcessWebhook $processWebhook
     * @param ConfigRepository $configProvider
     * @param JsonSerializer $jsonSerializer
     * @param File $file
     * @param TransactionRepository $transactionRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param Emulation $appEmulation
     */
    public function __construct(
        LogRepository $logRepository,
        ProcessWebhook $processWebhook,
        ConfigRepository $configProvider,
        JsonSerializer $jsonSerializer,
        File $file,
        TransactionRepository $transactionRepository,
        CartRepositoryInterface $quoteRepository,
        Emulation $appEmulation
    ) {
        $this->logRepository = $logRepository;
        $this->processWebhook = $processWebhook;
        $this->configProvider = $configProvider;
        $this->jsonSerializer = $jsonSerializer;
        $this->file = $file;
        $this->transactionRepository = $transactionRepository;
        $this->quoteRepository = $quoteRepository;
        $this->appEmulation = $appEmulation;
    }

    /**
     * @inheritDoc
     */
    public function processTransfer()
    {
        \TrueLayer\Settings::tlAgent('truelayer-magento/' . $this->configProvider->getExtensionVersion());
        $webhook = TrueLayerWebhook::configure()
            ->useProduction(!$this->configProvider->isSandbox($this->getStoreId()))
            ->create();

        $webhook->handler(function (TrueLayerWebhookInterface\EventInterface $event) {
            $this->logRepository->addDebugLog('Webhook', $event->getBody());
        })->handler(function (TrueLayerWebhookInterface\PaymentSettledEventInterface $event) {
            try {
                $this->appEmulation->startEnvironmentEmulation($this->getStoreId(), Area::AREA_FRONTEND, true);
                $this->processWebhook->execute($event->getBody()['payment_id'], $event->getBody()['user_id']);
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Webhook processTransfer', $exception->getMessage());
                throw new LocalizedException(__($exception->getMessage()));
            } finally {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        });
        try {
            $webhook->execute();
        } catch (Exception $e) {
            $this->logRepository->addErrorLog('Webhook', $e->getMessage());
        }
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        if ($this->storeId === null) {
            try {
                $post = $this->file->fileGetContents('php://input');
                $postArray = $this->jsonSerializer->unserialize($post);
                if (!isset($postArray['payment_id']) || !$this->isValidUuid((string)$postArray['payment_id'])) {
                    $this->storeId = 0;
                    return $this->storeId;
                }

                $transaction = $this->transactionRepository->getByUuid($postArray['payment_id']);
                if (!$quoteId = $transaction->getQuoteId()) {
                    $this->storeId = 0;
                    return $this->storeId;
                }

                $quote = $this->quoteRepository->get($quoteId);
                $this->storeId = $quote->getStoreId();
            } catch (\Exception $exception) {
                $this->logRepository->addErrorLog('Webhook processTransfer postData', $exception->getMessage());
                $this->storeId = 0;
                return $this->storeId;
            }
        }
        return $this->storeId;
    }

    /**
     * Check if string is valid Uuid
     *
     * @param string $paymentId
     * @return bool
     */
    private function isValidUuid(string $paymentId): bool
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        return preg_match($pattern, $paymentId) === 1;
    }
}