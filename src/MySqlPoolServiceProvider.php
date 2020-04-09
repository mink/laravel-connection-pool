<?php

declare(strict_types=1);

namespace X\LaravelSwoolePool;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class MySqlPoolServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Connection::resolverFor(
            'mysql', fn (
            $connection,
            $database,
            $prefix,
            $config
        ) => new MySqlConnection(
            $connection,
            $database,
            $prefix,
            $config
        ));
    }
}
