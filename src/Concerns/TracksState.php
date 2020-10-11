<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

use Closure;
use X\LaravelConnectionPool\ConnectionState;

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
        $this->state = ConnectionState::IN_USE;
        $result = parent::run($query, $bindings, $callback);
        $this->state = ConnectionState::NOT_IN_USE;
        return $result;
    }
}
