<?php

namespace X\LaravelSwoolePool\Capsule;

use Illuminate\Database\Connection;
use X\LaravelSwoolePool\DatabaseManager;
use Illuminate\Database\Capsule\Manager as BaseManager;
use Illuminate\Database\Connectors\ConnectionFactory;
use Swoole\Event;

class Manager extends BaseManager
{
    public function getAvailableConnection(): Connection
    {
        foreach($this->getDatabaseManager()->getConnections() as $connection)
        {
            if(!$connection->isActive())
            {
                return $this->manager->connection($connection->getName());
            }
        }
    }

    protected function setupManager(): void
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }

    public function addConnections(string $name, int $amount, array $config): void
    {
        for($i = 0; $i < $amount; $i++)
        {
            $connectionName = $name . '-' . ($i + 1);

            $config['name'] = $connectionName;

            $this->addConnection($config, $connectionName);

            if($i == 0)
            {
                $this->getDatabaseManager()->setDefaultConnection($connectionName);
            }

            $this->getConnection($connectionName);
        }

        Event::wait();
    }
}
