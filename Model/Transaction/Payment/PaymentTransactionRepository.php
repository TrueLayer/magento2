<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Payment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogService;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterfaceFactory;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;

/**
 * Transaction PaymentTransactionRepository class
 */
class PaymentTransactionRepository implements PaymentTransactionRepositoryInterface
{
    /**
     * @var PaymentTransactionDataInterfaceFactory
     */
    private $dataFactory;
    private PaymentTransactionResourceModel $resource;
    private LogService $logger;

    /**
     * PaymentTransactionRepository constructor.
     *
     * @param PaymentTransactionResourceModel $resource
     * @param PaymentTransactionDataInterfaceFactory $dataFactory
     * @param LogService $logger
     */
    public function __construct(
        PaymentTransactionResourceModel $resource,
        PaymentTransactionDataInterfaceFactory $dataFactory,
        LogService $logger
    ) {
        $this->resource = $resource;
        $this->dataFactory = $dataFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function create(): PaymentTransactionDataInterface
    {
        return $this->dataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): PaymentTransactionDataInterface
    {

        if (!$entityId) {
            $errorMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($errorMsg, 'EntityId'));
        } elseif (!$this->resource->isExists($entityId)) {
            $exceptionMsg = self::NO_SUCH_ENTITY_EXCEPTION;
            throw new NoSuchEntityException(__($exceptionMsg, $entityId));
        }
        return $this->dataFactory->create()
            ->load($entityId);
    }

    /**
     * @inheritDoc
     */
    public function getByOrderId(int $orderId): PaymentTransactionDataInterface
    {
        if (!$orderId) {
            $errorMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($errorMsg, 'OrderID'));
        } elseif (!$this->resource->isOrderIdExists($orderId)) {
            throw new NoSuchEntityException(__('No record found for OrderID: %1.', $orderId));
        }
        return $this->dataFactory->create()
            ->load($orderId, 'order_id');
    }

    /**
     * @inheritDoc
     */
    public function getByPaymentUuid(string $uuid): PaymentTransactionDataInterface
    {
        if (!$uuid) {
            $errorMsg = static::INPUT_EXCEPTION;
            throw new InputException(__($errorMsg, 'Uuid'));
        } elseif (!$this->resource->isUuidExists($uuid)) {
            throw new NoSuchEntityException(__('No record found for uuid: %1.', $uuid));
        }

        return $this->dataFactory->create()
            ->load($uuid, 'uuid');
    }

    /**
     * @inheritDoc
     */
    public function save(PaymentTransactionDataInterface $entity): PaymentTransactionDataInterface
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            $this->logger->error('Quote repository', $exception->getMessage());
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return $entity;
    }
}
