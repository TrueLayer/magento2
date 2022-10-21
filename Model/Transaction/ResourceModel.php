<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ResourceModel extends AbstractDb
{

    /**
     * Check is entity exists
     *
     * @param int $entityId
     * @return bool
     */
    public function isExists(int $entityId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'entity_id')
            ->where('entity_id = :entity_id');
        $bind = [':entity_id' => $entityId];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Check is entity exists
     *
     * @param int $quoteId
     * @return bool
     */
    public function isQuoteIdExists(int $quoteId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'quote_id')
            ->where('quote_id = :quote_id');
        $bind = [':quote_id' => $quoteId];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Check is entity exists
     *
     * @param int $orderId
     * @return bool
     */
    public function isOrderIdExists(int $orderId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'order_id')
            ->where('order_id = :order_id');
        $bind = [':order_id' => $orderId];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Check is entity exists
     *
     * @param string $uuid
     * @return bool
     */
    public function isUuidExists(string $uuid): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'uuid')
            ->where('uuid = :uuid');
        $bind = [':uuid' => $uuid];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Check is entity exists
     *
     * @param string $token
     * @return bool
     */
    public function isTokenExist(string $token): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'token')
            ->where('token = :token');
        $bind = [':token' => $token];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * @param $transaction
     * @return bool
     */
    public function lockTransaction($transaction)
    {
        $connection = $this->getConnection();
        return (bool)$connection->update(
            $this->getTable('truelayer_transaction'),
            ['is_locked' => 1],
            $connection->quoteInto('entity_id = ?', $transaction->getEntityId())
        );
    }

    /**
     * @param $transaction
     * @return bool
     */
    public function isLocked($transaction): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'is_locked')
            ->where('entity_id = :entity_id');
        $bind = [':entity_id' => $transaction->getEntityId()];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * @param $transaction
     * @return bool
     */
    public function isOrderPlaced($transaction): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_transaction'), 'order_id')
            ->where('entity_id = :entity_id');
        $bind = [':entity_id' => $transaction->getEntityId()];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('truelayer_transaction', 'entity_id');
    }
}
