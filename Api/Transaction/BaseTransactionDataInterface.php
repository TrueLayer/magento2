<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface for transaction model
 * @api
 */
interface BaseTransactionDataInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array.
     */
    public const ENTITY_ID = 'entity_id';
    public const ORDER_ID = 'order_id';
    public const STATUS = 'status';
    public const FAILURE_REASON = 'failure_reason';
    public const IS_LOCKED = 'is_locked';

    /**
     * Returns the ID.
     *
     * @return int ID.
     */
    public function getEntityId(): int;

    /**
     * Returns the order ID.
     *
     * @return int|null order ID.
     */
    public function getOrderId(): ?int;

    /**
     * Sets the order ID.
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Return the payment uuid.
     *
     * @return string|null
     */
    public function getPaymentUuid(): ?string;

    /**
     * Set the payment uuid.
     *
     * @param string $value
     * @return $this
     */
    public function setPaymentUuid(string $value): self;

    /**
     * Return status.
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set status.
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * Get failure reason.
     *
     * @return string|null
     */
    public function getFailureReason(): ?string;

    /**
     * Set failure reason.
     *
     * @param string $failureReason
     * @return $this
     */
    public function setFailureReason(string $failureReason): self;

    /**
     * Return is_locked.
     *
     * @return bool
     */
    public function getIsLocked(): bool;

    /**
     * Set is_locked.
     *
     * @param bool $isLocked
     * @return $this
     */
    public function setIsLocked(bool $isLocked): self;
}
