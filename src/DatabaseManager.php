<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;

class DatabaseManager extends BaseDatabaseManager
{
    /** @var MySqlConnection[] */
    protected $connections = [];

    protected int $maxConnections;
    protected int $minConnections;

    public const STATE_NOT_IN_USE = 0;
    public const STATE_IN_USE = 1;

    public function __construct($app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);

        $this->minConnections = $app['config']->get('database.min_connections');
        $this->maxConnections = $app['config']->get('database.max_connections');
    }

    public function getIdleConnections(): array
    {
        return array_filter(
            $this->connections,
            fn(MySqlConnection $connection) => $connection->getState() == self::STATE_NOT_IN_USE
        );
    }

    /**
     * Obtains an available connection and marks it as active.
     * The active state will be ignored if a connection name is declared.
     *
     * @param string|null $name
     * @throws Exception
     * @return Connection
     */
    public function connection($name = null): Connection
    {
        $connection = parent::connection($name);

        // ignore "active" state if connection name is declared
        if($name) {
            return $connection;
        }

        $name = $connection->getName();

        // is the selected connection idle?
        if($this->connections[$name]->getState() === self::STATE_NOT_IN_USE) {
            return $this->connections[$name]->setState(self::STATE_IN_USE);
        }

        foreach($this->getIdleConnections() as $connection) {
            // use the first available idle connection
            // mark as active
            return $connection->setState(self::STATE_IN_USE);
        }

        // no idle connections found, create a new connection if allowed
        if(count($this->connections) < $this->maxConnections) {
            $this->makeNewConnection();
            // go through the idle connections again
            // this connection should be here
            foreach($this->getIdleConnections() as $connection) {
                return $connection->setState(self::STATE_IN_USE);
            }
        }

        throw new Exception('There are no idle connections available');
    }

    /**
     * Opens connections based on in the config
     * Will open the minimum connections required.
     *
     * @return void
     */
    public function makeInitialConnections(): void
    {
        foreach($this->app['config']['database.connections'] as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if(!isset($this->connections[$name]) && count($this->connections) < $this->minConnections) {
                $this->connections[$name] = $this->configure(
                    $this->makeConnection($database), $type
                );
            }
        }
    }

    /**
     * Adds a new connection to the pool
     *
     * @throws Exception
     * @return void
     */
    public function makeNewConnection(): void
    {
        foreach($this->app['config']['database.connections'] as $name => $connection) {
            [$database, $type] = $this->parseConnectionName($name);
            if(!isset($this->connections[$name])) {
                if(count($this->connections) < $this->maxConnections) {
                    $this->connections[$name] = $this->configure(
                        $this->makeConnection($database), $type
                    );
                    break;
                } else {
                    throw new Exception('Cannot make a new connection: Connection pool is full');
                }
            }
        }
    }
}
