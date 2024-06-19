<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Refund;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RefundTransactionResourceModel extends AbstractDb
{
    /**
     * Resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('truelayer_refund_transaction', 'entity_id');
    }
}
