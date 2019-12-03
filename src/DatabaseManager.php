<?php

namespace X\LaravelSwoolePool;

use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager as BaseDatabaseManager;

class DatabaseManager extends BaseDatabaseManager
{
    public function getConnectionFactory(): ConnectionFactory
    {
        return $this->factory;
    }
}
