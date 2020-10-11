<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use X\LaravelConnectionPool\Exceptions\{
    ConnectionNotFoundException,
    ConnectionPoolFullException,
    NoConnectionsAvailableException
};

class DatabaseManager extends BaseDatabaseManager
{
    /** @var int */
    protected int $minConnections;

    /** @var int */
    protected int $maxConnections;

    /**
     * Create a new database manager instance with additional configuration.
     *
     * @param  mixed $app
     * @param ConnectionFactory $factory
     * @return void
     */
    public function __construct($app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);
    }

    /**
     * Obtain all of the idle connections.
     *
     * @return array
     */
    public function getIdleConnections(): array
    {
        return array_filter(
            $this->connections,
            fn (MySqlConnection $connection) => $connection->getState() == ConnectionState::NOT_IN_USE
        );
    }

    /**
     * Obtains an idle connection and marks it as active.
     * The active state will be ignored if a connection name is declared.
     *
     * @param string|null $name
     * @throws ConnectionPoolFullException|NoConnectionsAvailableException
     * @return Connection
     */
    public function connection($name = null): Connection
    {
        // is there a connection we can use before we make a new one?
        if ($name === null) {
            foreach ($this->getIdleConnections() as $connection) {
                // use the first available idle connection
                // mark as active
                return $connection->setState(ConnectionState::IN_USE);
            }
        }

        // obtain the connection by name, if it exists
        // if no name is provided, it will create the a connection from the config
        $connection = parent::connection($name);

        // ignore "active" state if connection name is declared
        if ($name) {
            return $connection;
        }

        $name = $connection->getName();

        // is the selected connection idle?
        if ($this->connections[$name]->getState() === ConnectionState::NOT_IN_USE) {
            return $this->connections[$name]->setState(ConnectionState::IN_USE);
        }

        foreach ($this->getIdleConnections() as $connection) {
            // use the first available idle connection
            // mark as active
            return $connection->setState(ConnectionState::IN_USE);
        }

        // no idle connections found, create a new connection if allowed
        if (count($this->connections) < $this->maxConnections) {
            $this->makeNewConnection();
            // go through the idle connections again
            // this connection should be here
            foreach ($this->getIdleConnections() as $connection) {
                return $connection->setState(ConnectionState::IN_USE);
            }
        }

        throw new NoConnectionsAvailableException();
    }

    /**
     * Opens connections based on in the config
     * Will open the minimum connections required.
     *
     * @return void
     */
    public function makeInitialConnections(): void
    {
        // recycle existing connections if any
        foreach ($this->connections as $connection) {
            $this->recycleConnection($connection->getName());
        }

        foreach ($this->app['config']['database.connections'] as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if (!isset($this->connections[$name]) && count($this->connections) < $this->minConnections) {
                $this->connections[$name] = $this->configure(
                    $this->makeConnection($database), $type
                );
            }
        }
    }

    /**
     * Adds a new connection to the pool.
     *
     * @throws ConnectionPoolFullException
     * @return void
     */
    public function makeNewConnection(): void
    {
        foreach ($this->app['config']['database.connections'] as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if (!isset($this->connections[$name])) {
                if (count($this->connections) < $this->maxConnections) {
                    $this->connections[$name] = $this->configure(
                        $this->makeConnection($database), $type
                    );
                    break;
                } else {
                    throw new ConnectionPoolFullException();
                }
            }
        }
    }

    /**
     * Recycle a connection by name from the pool.
     *
     * @param string $name
     * @throws ConnectionNotFoundException
     * @return void
     */
    public function recycleConnection(string $name): void
    {
        if (!$this->connections[$name]) {
            throw new ConnectionNotFoundException();
        }

        $this->connections[$name]->disconnect();
        unset($this->connections[$name]);
    }

    /**
     * Get the minimum connections.
     *
     * @return int
     */
    public function getMinConnections(): int
    {
        return $this->minConnections;
    }

    /**
     * Set the minimum connections.
     *
     * @param int $minConnections
     * @return $this
     */
    public function setMinConnections(int $minConnections): self
    {
        $this->minConnections = $minConnections;
        return $this;
    }

    /**
     * Get the maximum connections.
     *
     * @return int
     */
    public function getMaxConnections(): int
    {
        return $this->maxConnections;
    }

    /**
     * Set the maximum connections.
     *
     * @param int $maxConnections
     * @return $this
     */
    public function setMaxConnections(int $maxConnections): self
    {
        $this->maxConnections = $maxConnections;
        return $this;
    }
}
