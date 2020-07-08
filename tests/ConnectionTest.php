<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Swoole\Coroutine\Scheduler;
use X\LaravelConnectionPool\DatabaseManager;
use X\LaravelConnectionPool\MySqlConnection;

class ConnectionTest extends TestCase
{
    public function testConnectionsAreDifferent(): void
    {
        /** @var MySqlConnection $connection */
        $connection = $this->app['db']->connection();
        /** @var MySqlConnection $secondConnection */
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
        $this->assertTrue($connection->getState() === MySqlConnection::STATE_IN_USE);

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
        $this->assertFalse($connection->getState() === MySqlConnection::STATE_IN_USE);
    }

    public function testConnectionHasLabels(): void
    {
        /** @var MySqlConnection $connection */
        $connection = $this->app['db']->connection('mysql-1')
            ->addLabel('test')
            ->addLabel('another-label')
            ->addLabel('DELETE_ME')
            ->removeLabel('DELETE_ME');

        $this->assertEquals(['test', 'another-label'], $connection->getLabels());
    }

    public function testPopulatedConnections(): void
    {
        // 20 connections are defined in TestCase, but only 2 are populated initially
        $this->assertCount(2, $this->app['db']->getConnections());

        // let's add a new connection to the pool
        $this->app['db']->makeNewConnection();
        // there should now be 3 connections in the pool
        $this->assertCount(3, $this->app['db']->getConnections());

        // let's mark all 3 connections as active
        // this will force the pool to fetch a new connection automatically
        foreach($this->app['db']->getConnections() as $connection) {
            $connection->setState(MySqlConnection::STATE_IN_USE);
        }

        // fetching a new connection, as the pool has no idle connections
        /** @var MySqlConnection $newConnection */
        $newConnection = $this->app['db']->connection();

        // there should now be 4 connections in the pool
        $this->assertCount(4, $this->app['db']->getConnections());
    }

    public function testConnectionRecycled(): void
    {
        // 2 connections by default: "mysql-1" and "mysql-2"
        // let's remove connection "mysql-1"
        $this->app['db']->recycleConnection('mysql-1');

        // there should only be 1 connection available
        $this->assertCount(1, $this->app['db']->getConnections());

        // ..and that connection is "mysql-2"
        $this->assertEquals('mysql-2', array_key_first($this->app['db']->getConnections()));

        // seeing as the connection is not in use
        $this->assertEquals(MySqlConnection::STATE_NOT_IN_USE, $this->app['db']->getConnections()['mysql-2']->getState());

        // ...when we grab a connection it should be "mysql-2" by default
        $this->assertEquals('mysql-2', $this->app['db']->getName());

        // what about another connection? it should grab "mysql-1" before "mysql-3", "mysql-4" etc.
        $this->assertEquals('mysql-1', $this->app['db']->getName());

        // and another? it should grab "mysql-3" and continue in that order
        $this->assertEquals('mysql-3', $this->app['db']->getName());

        // now let's close "mysql-2"
        $this->app['db']->recycleConnection('mysql-2');

        // want another connection? you'll get "mysql-2", the others are still marked as active
        $this->assertEquals('mysql-2', $this->app['db']->getName());
    }
}
