<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Payment;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use TrueLayer\Connect\Api\Transaction\Payment\PaymentTransactionDataInterface;

/**
 * Transaction PaymentTransactionDataModel
 */
class PaymentTransactionDataModel extends AbstractModel implements ExtensibleDataInterface, PaymentTransactionDataInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(PaymentTransactionResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int)$this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): ?int
    {
        return $this->getData(self::QUOTE_ID)
            ? (int)$this->getData(self::QUOTE_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(int $quoteId): PaymentTransactionDataInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID)
            ? (int)$this->getData(self::ORDER_ID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): PaymentTransactionDataInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getToken(): ?string
    {
        return $this->getData(self::TOKEN)
            ? (string)$this->getData(self::TOKEN)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $value): PaymentTransactionDataInterface
    {
        return $this->setData(self::TOKEN, $value);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentUuid(): ?string
    {
        return $this->getData(self::UUID)
            ? (string)$this->getData(self::UUID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setPaymentUuid(string $value): PaymentTransactionDataInterface
    {
        return $this->setData(self::UUID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS)
            ? (string)$this->getData(self::STATUS)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): PaymentTransactionDataInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getFailureReason(): ?string
    {
        return $this->getData(self::FAILURE_REASON)
            ? (string)$this->getData(self::FAILURE_REASON)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setFailureReason(string $failureReason): PaymentTransactionDataInterface
    {
        return $this->setData(self::FAILURE_REASON, $failureReason);
    }

    /**
     * @inheritDoc
     */
    public function getInvoiceUuid(): ?string
    {
        return $this->getData(self::INVOICE_UUID)
            ? (string)$this->getData(self::INVOICE_UUID)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setInvoiceUuid(string $invoiceUuid): PaymentTransactionDataInterface
    {
        return $this->setData(self::INVOICE_UUID, $invoiceUuid);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentUrl(): ?string
    {
        return $this->getData(self::PAYMENT_URL)
            ? (string)$this->getData(self::PAYMENT_URL)
            : null;
    }

    /**
     * @inheritDoc
     */
    public function setPaymentUrl(string $paymentUrl): PaymentTransactionDataInterface
    {
        return $this->setData(self::PAYMENT_URL, $paymentUrl);
    }

    /**
     * @inheritDoc
     */
    public function getIsLocked(): bool
    {
        return (bool) $this->getData(self::IS_LOCKED);
    }

    /**
     * @inheritDoc
     */
    public function setIsLocked(bool $isLocked): PaymentTransactionDataInterface
    {
        return $this->setData(self::IS_LOCKED, $isLocked ? 1 : 0);
    }
}
