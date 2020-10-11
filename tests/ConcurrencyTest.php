<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Illuminate\Support\Facades\DB;
use Swoole\Event;
use Swoole\Runtime;
use X\LaravelConnectionPool\Exceptions\NoConnectionsAvailableException;
use X\LaravelConnectionPool\Tests\Models\User;

class ConcurrencyTest extends TestCase
{
    public function testConcurrentSleepQueries(): void
    {
        Runtime::enableCoroutine();

        $timeStarted = microtime(true);

        // complete x10 1s sleep queries concurrently
        // should take ~1s to execute
        for ($i = 0; $i < 10; $i++) {
            go(function () use ($i) {
                $this->app['db']
                    ->connection()
                    ->table(null)
                    ->select(DB::raw('SLEEP(1)'))
                    ->get();
            });
        }

        Event::wait();

        $timeFinished = microtime(true);

        // asserting that the execution of all 10 queries took under 1.1s
        $this->assertTrue(
            bccomp("1.1", strval($timeFinished - $timeStarted), 3) === 1
        );
    }

    public function testConcurrentQueriesWhenNotEnoughConnections(): void
    {
        Runtime::enableCoroutine();

        $exception = false;

        // attempt to complete x11 1s sleep queries concurrently
        // there are only 10 connections available to use at once
        for ($i = 0; $i < 11; $i++) {
            go(function () use ($i, &$exception) {
                try {
                    if (! $exception) {
                        $this->app['db']
                            ->connection()
                            ->table(null)
                            ->select(DB::raw('SLEEP(1)'))
                            ->get();
                    }
                } catch (NoConnectionsAvailableException $e) {
                    $exception = true;
                }
            });
        }
        Event::wait();

        // asserting that no connections were available to perform at least one of the queries
        $this->assertTrue($exception);
    }

    public function testConcurrentModelSaveQueries(): void
    {
        Runtime::enableCoroutine();

        // initially there are 2 connections
        $this->assertCount(2, $this->app->get('db')->getConnections());

        // these 2 connections should be idle
        $this->assertCount(2, $this->app->get('db')->getIdleConnections());

        // create 10 users at once
        for ($i = 0; $i < 10; $i++) {
            go(function () {
                $user = new User();
                $user->name = 'Zac';
                try {
                    $user->save();
                } catch (NoConnectionsAvailableException $e) {
                    // user didnt save, fail
                }
                // check that the user was created
                $this->assertNotNull($user->id);
            });
        }

        Event::wait();

        // 10 users created at once, therefore should be 10 connections in the pool
        $this->assertCount(10, $this->app->get('db')->getConnections());

        // ...and all 10 connections should be idle, since the queries have finished
        $this->assertCount(10, $this->app->get('db')->getIdleConnections());

        // recycle existing connections and open the initial minimum 2
        $this->app->get('db')->makeInitialConnections();

        // should be 2 connections again
        $this->assertCount(2, $this->app->get('db')->getConnections());
    }

    public function testConcurrentModelSelectQueries(): void
    {
        Runtime::enableCoroutine();

        // run x10 select queries
        for ($i = 0; $i < 10; $i++) {
            go(function () {
                $selectUser = User::where('id', 1)->first();
            });
        }

        Event::wait();

        // since they all ran at once, there should be 10 connections in the pool
        $this->assertCount(10, $this->app->get('db')->getConnections());

        // ...which are now idle - they have been used
        $this->assertCount(10, $this->app->get('db')->getIdleConnections());
    }

}