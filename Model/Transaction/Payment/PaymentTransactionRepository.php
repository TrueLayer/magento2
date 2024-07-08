<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Payment;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
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
    /**
     * @var PaymentTransactionCollectionFactory
     */
    private $collectionFactory;
    private PaymentTransactionResourceModel $resource;
    private LogServiceInterface $logger;

    /**
     * @param PaymentTransactionResourceModel $resource
     * @param PaymentTransactionDataInterfaceFactory $dataFactory
     * @param PaymentTransactionCollectionFactory $collectionFactory
     * @param LogServiceInterface $logger
     */
    public function __construct(
        PaymentTransactionResourceModel $resource,
        PaymentTransactionDataInterfaceFactory $dataFactory,
        PaymentTransactionCollectionFactory $collectionFactory,
        LogServiceInterface $logger
    ) {
        $this->resource = $resource;
        $this->dataFactory = $dataFactory;
        $this->collectionFactory = $collectionFactory;
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
        return $this->getByColumn('entity_id', $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getByOrderId(int $orderId): PaymentTransactionDataInterface
    {
        return $this->getByColumn('order_id', $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId): PaymentTransactionDataInterface
    {
        return $this->getByColumn('quote_id', $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getByPaymentUuid(string $uuid): PaymentTransactionDataInterface
    {
        return $this->getByColumn('uuid', $uuid);
    }

    /**
     * @inheritDoc
     */
    public function save(PaymentTransactionDataInterface $entity): PaymentTransactionDataInterface
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            $this->logger->error('Save payment transaction', $exception);
            $exceptionMsg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__(
                $exceptionMsg,
                $exception->getMessage()
            ));
        }
        return $entity;
    }

    /**
     * @param string $col
     * @param $value
     * @return PaymentTransactionDataInterface
     * @throws NoSuchEntityException
     */
    private function getByColumn(string $col, $value): PaymentTransactionDataInterface
    {
        $model = $this->create();
        $this->resource->load($model, $value, $col);

        if (!$model->getEntityId()) {
            $this->logger->error('Payment transaction not found', $value);
            throw new NoSuchEntityException(__('No record found for %1: %2.', $col, $value));
        }

        return $model;
    }

    /**
     * @param array $cols
     * @param array $sort
     * @return PaymentTransactionDataInterface
     */
    public function getOneByColumns(array $cols, array $sort = []): PaymentTransactionDataInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($cols as $col => $value) {
            $collection->addFieldToFilter($col, $value);
        }

        foreach ($sort as $col => $dir) {
            $collection->setOrder($col, $dir);
        }

        return $collection->getFirstItem();
    }
}
