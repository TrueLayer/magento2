<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction\Payment;

use TrueLayer\Connect\Api\Transaction\BaseTransactionDataInterface;

/**
 * Interface for transaction model
 * @api
 */
interface PaymentTransactionDataInterface extends BaseTransactionDataInterface
{
    /**
     * Constants for keys of data array.
     */
    public const QUOTE_ID = 'quote_id';
    public const UUID = 'uuid';
    public const TOKEN = 'token';
    public const AMOUNT = 'amount';
    public const AMOUNT_REQUIRES_VALIDATION = 'amount_requires_validation';
    public const PAYMENT_FAILED = 'payment_failed';
    public const PAYMENT_SETTLED = 'payment_settled';

    /**
     * Returns the quote ID.
     *
     * @return int|null quote ID.
     */
    public function getQuoteId(): ?int;

    /**
     * Sets the quote ID.
     *
     * @param int $quoteId
     * @return $this
     */
    public function setQuoteId(int $quoteId): self;

    /**
     * Return token.
     *
     * @return string|null
     */
    public function getToken(): ?string;

    /**
     * Set token.
     *
     * @param string $value
     * @return $this
     */
    public function setToken(string $value): self;

    /**
     * @return int|null
     */
    public function getAmount(): ?int;

    /**
     * @param int $amount
     * @return $this
     */
    public function setAmount(int $amount): self;

    /**
     * @return bool
     */
    public function amountRequiresValidation(): bool;

    /**
     * @param bool $bool
     * @return $this
     */
    public function setAmountRequiresValidation(bool $bool): self;

    /**
     * @return $this
     */
    public function setPaymentFailed(): self;

    /**
     * @return bool
     */
    public function isPaymentFailed(): bool;

    /**
     * @return $this
     */
    public function setPaymentSettled(): self;

    /**
     * @return bool
     */
    public function isPaymentSettled(): bool;
}
