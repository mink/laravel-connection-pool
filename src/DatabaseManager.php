<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\DatabaseManager as BaseDatabaseManager;
use X\LaravelConnectionPool\Exceptions\{
    ConnectionNotFoundException,
    ConnectionPoolFullException,
    NoConnectionsAvailableException
};

class DatabaseManager extends BaseDatabaseManager
{
    /**
     * @var array<array-key, MySqlConnection>
     */
    protected $connections = [];

    /**
     * The minimum amount of connections to be in the pool.
     * This amount of connections will be present in the pool at all times.
     *
     * @var int
     */
    protected int $minConnections = 1;

    /**
     * The maximum amount of connections allowed in the pool.
     *
     * @var int
     */
    protected int $maxConnections = 1;

    /**
     * Obtain a$ll of the idle connections.
     *
     * @return array<array-key, MySqlConnection>
     */
    public function getIdleConnections(): array
    {
        return array_filter(
            $this->connections,
            fn (MySqlConnection $connection) => $connection->getState() == ConnectionState::NOT_IN_USE
        );
    }

    /**
     * Return all of the created connections.
     *
     * @return array<array-key, MySqlConnection>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * Add a connection.
     *
     * @param string $name
     * @param MySqlConnection $connection
     * @return $this
     */
    public function addConnection(string $name, MySqlConnection $connection): self
    {
        $this->connections[$name] = $connection;
        return $this;
    }

    /**
     * Obtains an idle connection and marks it as active.
     * The active state will be ignored if a connection name is declared.
     *
     * @param string|null $name
     * @throws ConnectionPoolFullException|NoConnectionsAvailableException
     * @return MySqlConnection
     */
    public function connection($name = null): MySqlConnection
    {
        // is there a connection we can use before we make a new one?
        if ($name === null) {
            foreach ($this->getIdleConnections() as $connection) {
                // use the first available idle connection
                // mark as active
                return $connection;
            }
        }

        // obtain the connection by name, if it exists
        // if no name is provided, it will create the a connection from the config
        /** @var MySqlConnection $connection */
        $connection = parent::connection($name);

        // ignore "active" state if connection name is declared
        if ($name) {
            return $connection;
        }

        $name = $connection->getName();

        // is the selected connection idle?
        if (isset($this->connections[$name]) && $this->connections[$name]->getState() === ConnectionState::NOT_IN_USE) {
            return $this->connections[$name];
        }

        foreach ($this->getIdleConnections() as $connection) {
            // use the first available idle connection
            // mark as active
            return $connection;
        }

        // no idle connections found, create a new connection if allowed
        if (count($this->connections) < $this->maxConnections) {
            $this->makeNewConnection();
            // go through the idle connections again
            // this connection should be here
            foreach ($this->getIdleConnections() as $connection) {
                return $connection;
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

        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->get('config');

        /**
         * @var string $name
         * @var MySqlConnection $connection
         */
        foreach ($config->get('database.connections') as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if (! isset($this->connections[$name]) && count($this->connections) < $this->minConnections) {
                /** @var MySqlConnection $newConnection */
                $newConnection = $this->configure(
                    $this->makeConnection((string) $database), $type
                );
                $this->addConnection($name, $newConnection);
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
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->get('config');

        /**
         * @var string $name
         * @var MySqlConnection $connection
         */
        foreach ($config->get('database.connections') as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if (!isset($this->connections[$name])) {
                if (count($this->connections) < $this->maxConnections) {
                    /** @var MySqlConnection $newConnection */
                    $newConnection = $this->configure(
                        $this->makeConnection((string) $database), $type
                    );
                    $this->addConnection($name, $newConnection);
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
     * @param  ?string $name
     * @throws ConnectionNotFoundException
     * @return void
     */
    public function recycleConnection(string $name = null): void
    {
        if (! isset($this->connections[$name])) {
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
