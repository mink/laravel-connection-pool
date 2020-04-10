<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use X\LaravelConnectionPool\DatabaseManager;
use X\LaravelConnectionPool\MySqlPoolServiceProvider;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    protected function getPackageProviders($app)
    {
        return [MySqlPoolServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Opt for X\LaravelConnectionPool\DatabaseManager usage
        $app->singleton('db', fn ($app) => new DatabaseManager($app, $app['db.factory']));

        $connections = [];

        // create 20 mysql connections titled from "mysql-1" to "mysql-20"
        for($i = 0; $i < 20; $i++) {
            $connections['mysql-'.($i+1)] = [
                'driver'    => 'mysql',
                'host'      => env('DB_HOST'),
                'port' => env('DB_PORT'),
                'database'  => env('DB_DATABASE'),
                'username'  => env('DB_USER'),
                'password'  => env('DB_PASS'),
                'charset'   => 'utf8',
                'collation' => 'utf8_unicode_ci',
                'prefix'    => ''
            ];
        }
        $app['config']->set('database.default', 'mysql-1');
        $app['config']->set('database.connections', $connections);

        // Create instances of each defined connection
        $app['db']->makeConnections();
    }
}
