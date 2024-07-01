<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction\Payment;

use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Transaction repository interface
 * @api
 */
interface PaymentTransactionRepositoryInterface
{
    /**
     * Exception text
     */
    public const INPUT_EXCEPTION = 'An "%1" is needed. Set the "%1" and try again.';
    public const NO_SUCH_ENTITY_EXCEPTION = 'The transaction with id "%1" does not exist.';
    public const COULD_NOT_DELETE_EXCEPTION = 'Could not delete the transaction: %1';
    public const COULD_NOT_SAVE_EXCEPTION = 'Could not save the transaction: %1';

    /**
     * Loads a specified quote
     *
     * @param int $entityId
     *
     * @return PaymentTransactionDataInterface
     * @throws LocalizedException
     */
    public function get(int $entityId): PaymentTransactionDataInterface;

    /**
     * Return quote object
     *
     * @return PaymentTransactionDataInterface
     */
    public function create(): PaymentTransactionDataInterface;

    /**
     * Perform persist operations for one entity
     *
     * @param PaymentTransactionDataInterface $entity
     *
     * @return PaymentTransactionDataInterface
     * @throws LocalizedException
     */
    public function save(PaymentTransactionDataInterface $entity): PaymentTransactionDataInterface;

    /**
     * @param int $orderId
     *
     * @return PaymentTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): PaymentTransactionDataInterface;

    /**
     * @param int $quoteId
     *
     * @return PaymentTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId): PaymentTransactionDataInterface;

    /**
     * @param string $uuid
     *
     * @return PaymentTransactionDataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByPaymentUuid(string $uuid): PaymentTransactionDataInterface;
}
