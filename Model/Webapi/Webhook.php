<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Webapi;

use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use Magento\Quote\Api\CartRepositoryInterface;
use ReflectionException;
use TrueLayer\Connect\Api\Config\RepositoryInterface as ConfigRepository;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface as TransactionRepository;
use TrueLayer\Connect\Api\Webapi\WebhookInterface;
use TrueLayer\Connect\Helper\ValidationHelper;
use TrueLayer\Connect\Service\Order\PaymentUpdate\Exceptions\OrderNotReadyException;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentFailedService;
use TrueLayer\Connect\Service\Order\PaymentUpdate\PaymentSettledService;
use TrueLayer\Connect\Service\Order\RefundUpdate\RefundFailedService;
use TrueLayer\Exceptions\Exception;
use TrueLayer\Exceptions\InvalidArgumentException;
use TrueLayer\Exceptions\SignerException;
use TrueLayer\Exceptions\WebhookHandlerException;
use TrueLayer\Exceptions\WebhookHandlerInvalidArgumentException;
use TrueLayer\Exceptions\WebhookVerificationFailedException;
use TrueLayer\Interfaces\Webhook as TrueLayerWebhookInterface;
use TrueLayer\Settings;
use TrueLayer\Webhook as TrueLayerWebhook;

/**
 * Class Webhook
 */
class Webhook implements WebhookInterface
{
    private PaymentSettledService $paymentSettledService;
    private PaymentFailedService $paymentFailedService;
    private RefundFailedService $refundFailedService;
    private ConfigRepository $configProvider;
    private JsonSerializer $jsonSerializer;
    private File $file;
    private TransactionRepository $transactionRepository;
    private CartRepositoryInterface $quoteRepository;
    private LogRepository $logger;

    /**
     * @param PaymentSettledService $paymentSettledService
     * @param PaymentFailedService $paymentFailedService
     * @param RefundFailedService $refundFailedService
     * @param ConfigRepository $configProvider
     * @param JsonSerializer $jsonSerializer
     * @param File $file
     * @param TransactionRepository $transactionRepository
     * @param CartRepositoryInterface $quoteRepository
     * @param LogRepository $logger
     */
    public function __construct(
        PaymentSettledService $paymentSettledService,
        PaymentFailedService $paymentFailedService,
        RefundFailedService $refundFailedService,
        ConfigRepository        $configProvider,
        JsonSerializer          $jsonSerializer,
        File                    $file,
        TransactionRepository   $transactionRepository,
        CartRepositoryInterface $quoteRepository,
        LogRepository           $logger
    ) {
        $this->paymentSettledService = $paymentSettledService;
        $this->paymentFailedService = $paymentFailedService;
        $this->refundFailedService = $refundFailedService;
        $this->configProvider = $configProvider;
        $this->jsonSerializer = $jsonSerializer;
        $this->file = $file;
        $this->transactionRepository = $transactionRepository;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger->addPrefix('Webhook');
    }

    /**
     * @throws AuthorizationException
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws SignerException
     * @throws WebhookHandlerException
     * @throws WebhookHandlerInvalidArgumentException
     */
    public function processTransfer()
    {
        Settings::tlAgent('truelayer-magento/' . $this->configProvider->getExtensionVersion());

        $webhook = TrueLayerWebhook::configure()
            ->useProduction(!$this->configProvider->isSandbox($this->getStoreId()))
            ->create()
            ->handler(function (TrueLayerWebhookInterface\EventInterface $event) {
                $this->logger->debug('Body', $event->getBody());
            })
            ->handler(function (TrueLayerWebhookInterface\PaymentSettledEventInterface $event) {
                $this->paymentSettledService->handle($event->getPaymentId());
            })
            ->handler(function (TrueLayerWebhookInterface\PaymentFailedEventInterface $event) {
                $this->paymentFailedService->handle($event->getPaymentId(), $event->getFailureReason());
            })
            ->handler(function (TrueLayerWebhookInterface\RefundFailedEventInterface $event) {
                $this->refundFailedService->handle($event->getRefundId(), $event->getFailureReason());
            });

        try {
            $webhook->execute();
        } catch (WebhookVerificationFailedException $e) {
            $this->logger->error('Invalid signature');
            throw new AuthorizationException(__('Invalid signature')); // 401
        } catch (NoSuchEntityException $e) {
            // We intentionally do not surface a 404 status code
            $this->logger->error('Aborting webhook, payment or refund not found');
        } catch (OrderNotReadyException $e) {
            $this->logger->error('Order not ready, webhook will be retried.');
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Webhook error', $e);
            throw $e;
        }
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        try {
            $paymentId = $this->getPaymentId();
            if (!$paymentId) {
                return 0;
            }

            $transaction = $this->transactionRepository->getByPaymentUuid($paymentId);
            if (!$quoteId = $transaction->getQuoteId()) {
                return 0;
            }

            $quote = $this->quoteRepository->get($quoteId);
            return $quote->getStoreId();
        } catch (\Exception $exception) {
            $this->logger->error('Unable to get store id', $exception);
            return 0;
        }
    }

    /**
     * @return string|null
     * @throws FileSystemException
     */
    private function getPaymentId(): ?string
    {
        $post = $this->file->fileGetContents('php://input');
        $postArray = $this->jsonSerializer->unserialize($post);

        if (!isset($postArray['payment_id']) || !ValidationHelper::isUUID((string) $postArray['payment_id'])) {
            return null;
        }

        return $postArray['payment_id'];
    }
}
