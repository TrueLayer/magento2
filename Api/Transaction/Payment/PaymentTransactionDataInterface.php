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
    public const INVOICE_UUID = 'invoice_uuid';
    public const PAYMENT_URL = 'payment_url';

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
     * Return invoice_uuid.
     *
     * @return string|null
     */
    public function getInvoiceUuid(): ?string;

    /**
     * Set invoice_uid.
     *
     * @param string $invoiceUuid
     * @return $this
     */
    public function setInvoiceUuid(string $invoiceUuid): self;

    /**
     * Return payment_url.
     *
     * @return string|null
     */
    public function getPaymentUrl(): ?string;

    /**
     * Set payment_url.
     *
     * @param string $paymentUrl
     * @return $this
     */
    public function setPaymentUrl(string $paymentUrl): self;
}
