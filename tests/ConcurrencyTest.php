<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Swoole\Coroutine\Scheduler;
use Swoole\Event;
use Swoole\Runtime;
use X\LaravelConnectionPool\MySqlConnection;
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
}
