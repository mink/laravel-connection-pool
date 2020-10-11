<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

class MySqlConnection extends BaseMySqlConnection
{
    use Concerns\HasLabels;
    use Concerns\TracksState;
}
