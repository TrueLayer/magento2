<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use TrueLayer\Connect\Api\Transaction\Data\DataInterface;
use TrueLayer\Connect\Api\Transaction\Data\SearchResultsInterface;
use TrueLayer\Connect\Model\Transaction\Collection;
use TrueLayer\Connect\Model\Transaction\DataModel;

/**
 * Transaction repository interface
 * @api
 */
interface RepositoryInterface
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
     * @return DataInterface
     * @throws LocalizedException
     */
    public function get(int $entityId): DataInterface;

    /**
     * Return quote object
     *
     * @return DataInterface
     */
    public function create(): DataInterface;

    /**
     * Retrieves an quote matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     *
     * @return SearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Get data collection by set of attribute values
     *
     * @param array $dataSet
     * @param bool $getFirst
     *
     * @return Collection|DataModel
     */
    public function getByDataSet(array $dataSet, bool $getFirst = false);

    /**
     * Register entity to delete
     *
     * @param DataInterface $entity
     *
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(DataInterface $entity): bool;

    /**
     * Deletes entity by ID
     *
     * @param int $entityId
     *
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById(int $entityId): bool;

    /**
     * Perform persist operations for one entity
     *
     * @param DataInterface $entity
     *
     * @return DataInterface
     * @throws LocalizedException
     */
    public function save(DataInterface $entity): DataInterface;

    /**
     * @param int $quoteId
     * @param bool $uuidCheck
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId, bool $uuidCheck = false): DataInterface;

    /**
     * @param int $orderId
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): DataInterface;

    /**
     * @param string $uuid
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByPaymentUuid(string $uuid): DataInterface;

    /**
     * @param string $token
     *
     * @return DataInterface
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByToken(string $token): DataInterface;

    /**
     * Lock Transaction
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function lock(DataInterface $entity): bool;

    /**
     * Unlock transaction
     *
     * @param DataInterface $entity
     *
     * @return DataInterface
     * @throws LocalizedException
     */
    public function unlock(DataInterface $entity): DataInterface;

    /**
     * Check if transaction is locked
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function isLocked(DataInterface $entity): bool;

    /**
     * Check if order is placed for this transaction
     *
     * @param DataInterface $entity
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkOrderIsPlaced(DataInterface $entity): bool;
}
