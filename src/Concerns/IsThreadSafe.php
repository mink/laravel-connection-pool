<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

use Illuminate\Database\Eloquent\Model;
use X\LaravelConnectionPool\ConnectionState;

trait IsThreadSafe
{
    /**
     * Assigns event listeners to ensure an idle connection is used.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function (Model $model) {
            $model->getConnection()->setState(ConnectionState::NOT_IN_USE);
            $model->setConnection(static::resolveConnection()->getName());
        });

        static::saved(function (Model $model) {
            $model->getConnection()->setState(ConnectionState::NOT_IN_USE);
        });
    }
}
