<?php
/**
 * Copyright © TrueLayer Ltd. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace TrueLayer\Connect\Api\User;

/**
 * User repository interface
 * @api
 */
interface RepositoryInterface
{
    /**
     * Set new user
     *
     * @param $email
     * @param $userId
     * @return bool
     */
    public function set($email, $userId): bool;

    /**
     * Get user by email
     *
     * @param $email
     * @return mixed
     */
    public function get($email);

    /**
     * Get user by id
     *
     * @param $userId
     * @return mixed
     */
    public function getByTruelayerId($userId);
}
