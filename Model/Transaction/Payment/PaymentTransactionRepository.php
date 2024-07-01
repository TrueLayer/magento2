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
use TrueLayer\Connect\Api\Log\LogServiceInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterfaceFactory;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionRepositoryInterface;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;

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
    private LogServiceInterface $logger;

    /**
     * PaymentTransactionRepository constructor.
     *
     * @param PaymentTransactionResourceModel $resource
     * @param PaymentTransactionDataInterfaceFactory $dataFactory
     * @param LogServiceInterface $logger
     */
    public function __construct(
        PaymentTransactionResourceModel $resource,
        PaymentTransactionDataInterfaceFactory $dataFactory,
        LogServiceInterface $logger
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
}
