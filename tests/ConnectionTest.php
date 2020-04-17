<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Swoole\Coroutine\Scheduler;
use X\LaravelConnectionPool\MySqlConnection;

class ConnectionTest extends TestCase
{
    public function testConnectionsAreDifferent(): void
    {
        $connection = $this->app['db']->connection();
        $secondConnection = $this->app['db']->connection();

        $this->assertEquals($connection->getName(), 'mysql-1');
        $this->assertEquals($secondConnection->getName(), 'mysql-2');

        $this->assertNotEquals(
            $connection->getName(),
            $secondConnection->getName()
        );
    }

    public function testConnectionActiveState(): void
    {
        /** @var MySqlConnection $connection */
        $connection = $this->app['db']->connection();

        // The connection has been obtained, thus it is set to active
        // to prepare for execution. This is to better support unbuffered queries.
        $this->assertTrue($connection->isActive());

        $runtime = new Scheduler();

        $runtime->add(function() use($connection) {
            // Run an update query concurrently.
            $connection->table('users')
                ->where('name', 'Zac')
                ->update(['password' => 'lol']);
        });

        $runtime->start();

        // The query has executed, so the connection is no longer in use
        // and is available to be used again.
        $this->assertFalse($connection->isActive());
    }

    public function testConnectionHasLabels(): void
    {
        $connection = $this->app['db']->connection('mysql-1')
            ->addLabel('test')
            ->addLabel('another-label')
            ->addLabel('DELETE_ME')
            ->removeLabel('DELETE_ME');

        $this->assertEquals(['test', 'another-label'], $connection->getLabels());
    }
}
