<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 *
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\User;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Resource Model for truelayer_user
 */
class ResourceModel extends AbstractDb
{

    /**
     * Resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('truelayer_user', 'entity_id');
    }

    /**
     * @param string $email
     * @return mixed
     */
    public function get(string $email)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_user'))
            ->where('magento_email = :email');
        $bind = [':email' => $email];
        return $connection->fetchRow($select, $bind);
    }

    /**
     * @param string $email
     * @param string $userId
     * @return bool
     */
    public function set(string $email, string $userId): bool
    {
        $connection = $this->getConnection();
        return (bool)$connection->insert(
            $this->getTable('truelayer_user'),
            ['magento_email' => $email, 'truelayer_id' => $userId]
        );
    }

    /**
     * @param string $uuid
     * @return mixed
     */
    public function getByTruelayerId(string $userId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable('truelayer_user'))
            ->where('truelayer_id = :userId');
        $bind = [':userId' => $userId];
        return $connection->fetchRow($select, $bind);
    }
}
