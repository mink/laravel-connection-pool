<?php

namespace X\LaravelSwoolePool;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;

class DatabaseManager extends BaseDatabaseManager
{
    /**
     * Obtains an available connection and marks it as active.
     * The active state will be ignored if a connection name is declared.
     * @param null $name
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

        if($this->connections[$name]->isActive()) {
            foreach($this->connections as $connection) {
                if(! $connection->isActive()) {
                    $connection->setActive(true);
                    return $connection;
                }
            }
        }

        $this->connections[$name]->setActive(true);

        return $this->connections[$name];
    }

    /**
     * Opens connections to every connection defined in the config
     */
    public function makeConnections(): void
    {
        foreach($this->app['config']['database.connections'] as $name => $connection)
        {
            [$database, $type] = $this->parseConnectionName($name);

            $name = $name ?: $database;

            if(!isset($this->connections[$name])) {
                $this->connections[$name] = $this->configure(
                    $this->makeConnection($database), $type
                );
            }
        }
    }
}
