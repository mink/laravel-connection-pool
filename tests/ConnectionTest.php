<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Illuminate\Support\Facades\DB;
use Swoole\Event;
use Swoole\Runtime;
use X\LaravelConnectionPool\ConnectionState;
use X\LaravelConnectionPool\MySqlConnection;

class ConnectionTest extends TestCase
{
    /**
     * Tests the label connection functionality.
     *
     * @return void
     */
    public function testConnectionHasLabels(): void
    {
        // add "test" and "another-label" labels to this connection
        // "DELETE_ME" is added but removed straight after
        /** @var MySqlConnection $connection */
        $connection = $this->app['db']->connection('mysql-1')
            ->addLabel('test')
            ->addLabel('another-label')
            ->addLabel('DELETE_ME')
            ->removeLabel('DELETE_ME');

        // the connection should only have the "test" and "another-label" labels
        $this->assertEquals(['test', 'another-label'], $connection->getLabels());
    }

    /**
     * Tests the connection state functionality.
     * Ensures that connections can only be selected if idle.
     *
     * @return void
     */
    public function testPopulatedConnections(): void
    {
        // 10 connections are defined in TestCase, but only 2 are populated initially
        $this->assertCount(2, $this->app['db']->getConnections());

        // let's add a new connection to the pool
        $this->app['db']->makeNewConnection();
        // there should now be 3 connections in the pool
        $this->assertCount(3, $this->app['db']->getConnections());

        // let's mark all 3 connections as active
        // this will force the pool to fetch a new connection automatically
        foreach($this->app['db']->getConnections() as $connection) {
            $connection->setState(ConnectionState::IN_USE);
        }

        // fetching a new connection, as the pool has no idle connections
        /** @var MySqlConnection $newConnection */
        $newConnection = $this->app['db']->connection();

        // there should now be 4 connections in the pool
        $this->assertCount(4, $this->app['db']->getConnections());
    }

    /**
     * Tests that the connection switches to another
     * when the initial connection is in use.
     *
     * @return void
     */
    public function testConnectionSwitchWhenActive(): void
    {
        Runtime::enableCoroutine();

        $timeStarted = microtime(true);

        go(function () {
            // set the current connection's state to "in use", so it can't be used
            $connection = $this->app->get('db')
                ->connection()
                ->setState(ConnectionState::IN_USE);
            // the query will automatically be performed on a different connection
            $connection->query()
                ->select(DB::raw('SLEEP(1)'))
                ->get();
        });

        Event::wait();

        $timeFinished = microtime(true);

        // asserting that the execution of the query took ~1s
        // the query should execute, it will look for another available connection
        $this->assertTrue(
            bccomp("1.1", strval($timeFinished - $timeStarted), 3) === 1
        );
    }
}
