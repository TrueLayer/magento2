<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\Transaction\Data;

use Magento\Framework\Api\SearchResultsInterface as FrameworkSearchResultsInterface;

/**
 * Interface for transaction search results.
 * @api
 */
interface SearchResultsInterface extends FrameworkSearchResultsInterface
{

    /**
     * Gets transaction items.
     *
     * @return DataInterface[]
     */
    public function getItems(): array;

    /**
     * Sets transaction items.
     *
     * @param DataInterface[] $items
     * @return $this
     */
    public function setItems(array $items): SearchResultsInterface;
}
