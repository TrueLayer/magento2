<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Model\User;

use TrueLayer\Connect\Api\User\RepositoryInterface;

/**
 * User PaymentTransactionRepository class
 */
class Repository implements RepositoryInterface
{
    /**
     * @var ResourceModel
     */
    private $resource;

    /**
     * PaymentTransactionRepository constructor.
     *
     * @param ResourceModel $resource
     */
    public function __construct(
        ResourceModel $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function set($email, $userId): bool
    {
        return $this->resource->set($email, $userId);
    }

    /**
     * @inheritDoc
     */
    public function get($email)
    {
        return $this->resource->get($email);
    }

    /**
     * @inheritDoc
     */
    public function getByTruelayerId($userId)
    {
        return $this->resource->getByTruelayerId($userId);
    }
}
