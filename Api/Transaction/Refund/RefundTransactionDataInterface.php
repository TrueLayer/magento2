<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction\Refund;

use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;

/**
 * Interface for refund transaction model
 * @api
 */
interface RefundTransactionDataInterface extends BaseTransactionDataInterface
{
    /**
     * Constants for keys of data array.
     */
    public const CREDITMEMO_ID = 'creditmemo_id';
    public const PAYMENT_UUID = 'payment_uuid';
    public const REFUND_UUID = 'refund_uuid';
    public const AMOUNT = 'amount';

    /**
     * @return string|null
     */
    public function getRefundUuid(): ?string;

    /**
     * @param string $value
     * @return $this
     */
    public function setRefundUuid(string $value): self;

    /**
     * @return int|null
     */
    public function getAmount(): ?int;

    /**
     * @param int $value
     * @return $this
     */
    public function setAmount(int $value): self;

    /**
     * Returns the credit memo ID
     * @return int|null
     */
    public function getCreditMemoId(): ?int;

    /**
     * @param int $creditMemoId
     * @return $this
     */
    public function setCreditMemoId(int $creditMemoId): self;

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
}
