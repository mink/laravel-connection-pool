<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Swoole\Event;
use Swoole\Runtime;
use X\LaravelConnectionPool\Exceptions\NoConnectionsAvailableException;

class ConcurrencyTest extends TestCase
{
    public function testConcurrentSleepQueries(): void
    {
        Runtime::enableCoroutine();

        $timeStarted = microtime(true);

        // complete x10 1s sleep queries concurrently
        // should take ~1s to execute
        for ($i = 0; $i < 10; $i++) {
            go(function () use($i) {
                $this->app['db']->connection()->getPdo()->query('SELECT SLEEP(1)');
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
            go(function () use($i, &$exception) {
                try {
                    if (! $exception) {
                        $this->app['db']->connection()->getPdo()->query('SELECT SLEEP(1)');
                    }
                } catch(NoConnectionsAvailableException $e) {
                    $exception = true;
                }
            });
        }
        Event::wait();

        // asserting that no connections were available to perform at least one of the queries
        $this->assertTrue($exception);
    }
}
