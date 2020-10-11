<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

use X\LaravelConnectionPool\ConnectionState;

trait IsThreadSafe
{
    /**
     * Save the model to the database.
     * The model will obtain a fresh connection to perform the update.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        // todo - make connection not in use before this save call is made
        $this->getConnection()->setState(ConnectionState::NOT_IN_USE);

        // obtain fresh connection from pool
        $this->setConnection(static::resolveConnection()->getName());

        // perform save
        $result = parent::save($options);

        // free connection to pool
        $this->getConnection()->setState(ConnectionState::NOT_IN_USE);

        return $result;
    }
}
