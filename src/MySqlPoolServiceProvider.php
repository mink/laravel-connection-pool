<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;

class MySqlPoolServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerConnectionResolver();
    }

    /**
     * Register the connection pool resolver.
     *
     * @return void
     */
    protected function registerConnectionResolver(): void
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
