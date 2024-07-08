<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Refund;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Log\LogServiceInterface as LogRepository;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionRepositoryInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterfaceFactory;

class RefundTransactionRepository implements RefundTransactionRepositoryInterface
{
    /**
     * @var RefundTransactionDataInterfaceFactory
     */
    private $dataFactory;

    /**
     * @var RefundCollectionFactory
     */
    private $collectionFactory;

    private RefundTransactionResourceModel $resource;
    private LogServiceInterface $logger;

    /**
     * @param RefundTransactionResourceModel $resource
     * @param RefundTransactionDataInterfaceFactory $dataFactory
     * @param RefundCollectionFactory $collectionFactory
     * @param LogRepository $logger
     */
    public function __construct(
        RefundTransactionResourceModel $resource,
        RefundTransactionDataInterfaceFactory $dataFactory,
        RefundCollectionFactory $collectionFactory,
        LogRepository $logger
    ) {
        $this->resource = $resource;
        $this->dataFactory = $dataFactory;
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function create(): RefundTransactionDataInterface
    {
        return $this->dataFactory->create();
    }

    /**
     * @inheritDoc
     */
    public function get(int $entityId): RefundTransactionDataInterface
    {
        return $this->getByColumn(RefundTransactionDataInterface::ENTITY_ID, $entityId);
    }

    /**
     * @inheritDoc
     */
    public function getByOrderId(int $orderId): RefundTransactionDataInterface
    {
        return $this->getByColumn(RefundTransactionDataInterface::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getByPaymentUuid(string $uuid): RefundTransactionDataInterface
    {
        return $this->getByColumn(RefundTransactionDataInterface::PAYMENT_UUID, $uuid);
    }

    /**
     * @inheritDoc
     */
    public function getByRefundUuid(string $uuid): RefundTransactionDataInterface
    {
        return $this->getByColumn(RefundTransactionDataInterface::REFUND_UUID, $uuid);
    }

    /**
     * @inheritDoc
     */
    public function getByCreditMemoId(int $id): RefundTransactionDataInterface
    {
        return $this->getByColumn(RefundTransactionDataInterface::CREDITMEMO_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function save(RefundTransactionDataInterface $entity): RefundTransactionDataInterface
    {
        try {
            $this->resource->save($entity);
            return $entity;
        } catch (\Exception $exception) {
            $this->logger->error('Could not save refund transaction', $exception);
            $msg = self::COULD_NOT_SAVE_EXCEPTION;
            throw new CouldNotSaveException(__($msg, $exception->getMessage()));
        }
    }

    /**
     * @param string $col
     * @param $value
     * @return RefundTransactionDataInterface
     * @throws NoSuchEntityException
     */
    private function getByColumn(string $col, $value): RefundTransactionDataInterface
    {
        /** @var RefundTransactionDataInterface $transaction */
        $transaction = $this->dataFactory->create()->load($value, $col);

        if (!$transaction->getEntityId()) {
            $this->logger->error('Refund transaction not found', $value);
            throw new NoSuchEntityException(__('No record found for %1: %2.', $col, $value));
        }

        return $transaction;
    }

    /**
     * @param array $cols
     * @param array $sort
     * @return RefundTransactionDataInterface
     */
    public function getOneByColumns(array $cols, array $sort = []): RefundTransactionDataInterface
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
