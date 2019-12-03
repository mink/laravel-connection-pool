<?php

namespace X\LaravelSwoolePool;

use X\LaravelSwoolePool\Capsule\Manager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class MySqlPoolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     */
    public function boot(): void
    {
        //
    }
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('db', fn($container) => new Manager($container));
        $this->app->resolving('db', function ($db) {
            $db->getDatabaseManager()->extend('mysql', function ($config, $name) {

                $connection = $this->app->get('db')->getDatabaseManager()->getConnectionFactory()->make($config, $name);

                $newConnection = new MySqlConnection(
                    $connection->getPdo(),
                    $connection->getDatabaseName(),
                    $connection->getTablePrefix(),
                    $config
                );

                return $newConnection;
            });
        });
    }
}
