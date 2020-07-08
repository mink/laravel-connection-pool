<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Swoole\Coroutine\Scheduler;
use Swoole\Event;
use Swoole\Runtime;
use X\LaravelConnectionPool\MySqlConnection;

class ConcurrencyTest extends TestCase
{
    public function testConcurrentSleepQueries(): void
    {
        Runtime::enableCoroutine();

        $timeStarted = microtime(true);

        // complete x5 1s sleep queries concurrently
        // should take ~1s to execute
        for ($i = 0; $i < 20; $i++) {
            go(function () use($i) {
                $this->app['db']->connection('mysql-' . ($i + 1))->getPdo()->query('SELECT SLEEP(1)');
            });
        }

        Event::wait();

        $timeFinished = microtime(true);

        // asserting that the execution of all 5 queries took under 1.1s
        $this->assertTrue(
            bccomp("1.1", strval($timeFinished - $timeStarted), 3) === 1
        );
    }
}
