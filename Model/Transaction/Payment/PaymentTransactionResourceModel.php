<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Payment;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PaymentTransactionResourceModel extends AbstractDb
{
    /**
     * Resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('truelayer_transaction', 'entity_id');
    }

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
}
