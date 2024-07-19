<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction\Refund;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Transaction\BaseTransactionRepositoryInterface;

/**
 * Transaction repository interface
 * @api
 */
interface RefundTransactionRepositoryInterface
{
    /**
     * Exception text
     */
    public const INPUT_EXCEPTION = 'An "%1" is needed. Set the "%1" and try again.';
    public const NO_SUCH_ENTITY_EXCEPTION = 'The transaction with id "%1" does not exist.';
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the transaction: %1';
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the transaction: %1';

    /**
     * Get a refund transaction
     *
     * @param int $entityId
     *
     * @return RefundTransactionDataInterface
     * @throws NoSuchEntityException
     */
    public function get(int $entityId): RefundTransactionDataInterface;

    /**
     * Create a refund transaction
     *
     * @return RefundTransactionDataInterface
     */
    public function create(): RefundTransactionDataInterface;

    /**
     * Persist a refund transaction
     *
     * @param RefundTransactionDataInterface $entity
     *
     * @return RefundTransactionDataInterface
     * @throws LocalizedException
     */
    public function save(RefundTransactionDataInterface $entity): RefundTransactionDataInterface;

    /**
     * @param int $orderId
     *
     * @return RefundTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): RefundTransactionDataInterface;

    /**
     * @param string $uuid
     *
     * @return RefundTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByPaymentUuid(string $uuid): RefundTransactionDataInterface;

    /**
     * @param string $uuid
     *
     * @return RefundTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByRefundUuid(string $uuid): RefundTransactionDataInterface;

    /**
     * @param int $id
     * @return RefundTransactionDataInterface
     * @throws NoSuchEntityException
     */
    public function getByCreditMemoId(int $id): RefundTransactionDataInterface;

    /**
     * @param array $cols
     * @param array $sort
     * @return null|RefundTransactionDataInterface
     */
    public function getOneByColumns(array $cols, array $sort = []): ?RefundTransactionDataInterface;
}
