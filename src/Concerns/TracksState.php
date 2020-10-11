<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

use Closure;
use X\LaravelConnectionPool\ConnectionState;
use X\LaravelConnectionPool\DatabaseManager;
use X\LaravelConnectionPool\Exceptions\ConnectionInUseException;
use X\LaravelConnectionPool\MySqlConnection;

trait TracksState
{
    /**
     * The state of the connection.
     *
     * @var int
     */
    public int $state = ConnectionState::NOT_IN_USE;

    /**
     * Gets the connection state.
     *
     * @return int
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * Sets the state of the connection.
     *
     * @param int $state
     * @return $this
     */
    public function setState(int $state): self
    {
        $this->state = $state;
        return $this;
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  Closure  $callback
     * @return mixed
     *
     * @throws \Illuminate\Database\QueryException
     */
    protected function run($query, $bindings, Closure $callback)
    {
        if ($this->getState() === ConnectionState::NOT_IN_USE) {
            $this->setState(ConnectionState::IN_USE);

            /** @var bool $result */
            $result = parent::run($query, $bindings, $callback);

            $this->state = ConnectionState::NOT_IN_USE;

            return $result;
        }

        // if the database manager isn't set, the current connection
        // has no way or obtaining a different connection
        if (! $this->getDatabaseManager()) {
            throw new ConnectionInUseException();
        }

        /** @var DatabaseManager $manager */
        $manager = $this->getDatabaseManager();
        $connection = $manager->connection();

        // don't set state, the new connection has this same method
        // and doesn't call parent.. so it will do the above state check
        /** @var bool $result */
        $result = $connection->run($query, $bindings, $callback);

        $connection->setState(ConnectionState::NOT_IN_USE);

        return $result;
    }
}
