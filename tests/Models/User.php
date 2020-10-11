<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use X\LaravelConnectionPool\Concerns\IsThreadSafe;

class User extends Model
{
    use IsThreadSafe;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
}
