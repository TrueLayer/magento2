<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Refund;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;
use TrueLayer\Connect\Api\Transaction\Refund\RefundTransactionDataInterface;

class RefundTransactionDataModel extends AbstractModel implements ExtensibleDataInterface, RefundTransactionDataInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(RefundTransactionResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getEntityId(): int
    {
        return (int) $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function getRefundUuid(): ?string
    {
        return $this->getData(self::REFUND_UUID);
    }

    /**
     * @inheritDoc
     */
    public function setRefundUuid(string $value): RefundTransactionDataInterface
    {
        return $this->setData(self::REFUND_UUID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getAmount(): ?int
    {
        return (int) $this->getData(self::AMOUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAmount(int $value): RefundTransactionDataInterface
    {
        return $this->setData(self::AMOUNT, $value);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId(): ?int
    {
        return (int) $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId(int $orderId): RefundTransactionDataInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentUuid(): ?string
    {
        return $this->getData(self::PAYMENT_UUID);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentUuid(string $value): RefundTransactionDataInterface
    {
        return $this->setData(self::PAYMENT_UUID, $value);
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): ?string
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus(string $status): RefundTransactionDataInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getFailureReason(): ?string
    {
        return $this->getData(self::FAILURE_REASON);
    }

    /**
     * @inheritDoc
     */
    public function setFailureReason(string $failureReason): RefundTransactionDataInterface
    {
        return $this->setData(self::FAILURE_REASON, $failureReason);
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
    public function setIsLocked(bool $isLocked): RefundTransactionDataInterface
    {
        return $this->setData(self::IS_LOCKED, $isLocked ? 1 : 0);
    }

    /**
     * @inheritDoc
     */
    public function getCreditMemoId(): ?int
    {
        return (int) $this->getData(self::CREDITMEMO_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCreditMemoId(int $creditMemoId): RefundTransactionDataInterface
    {
        return $this->setData(self::CREDITMEMO_ID, $creditMemoId);
    }
}
