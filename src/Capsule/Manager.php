<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Capsule;

use Illuminate\Database\Capsule\Manager as BaseManager;
use Illuminate\Database\Connectors\ConnectionFactory;
use X\LaravelConnectionPool\DatabaseManager;

/** @psalm-suppress PropertyNotSetInConstructor */
class Manager extends BaseManager
{
    /**
     * Build the database manager instance.
     *
     * @psalm-suppress ArgumentTypeCoercion
     * @return void
     */
    protected function setupManager(): void
    {
        $factory = new ConnectionFactory($this->container);
        $this->manager = new DatabaseManager($this->container, $factory);
    }
}
