<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\Transaction\Refund;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method RefundTransactionDataModel getFirstItem()
 */
class RefundCollection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(RefundTransactionDataModel::class, RefundTransactionResourceModel::class);
    }
}
