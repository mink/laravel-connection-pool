<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

class MySqlConnection extends BaseMySqlConnection
{
    use Concerns\HasLabels,
        Concerns\TracksState;

    const STATE_NOT_IN_USE = 0;
    const STATE_IN_USE = 1;
}
