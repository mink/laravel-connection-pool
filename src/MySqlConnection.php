<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

/** @psalm-suppress PropertyNotSetInConstructor */
class MySqlConnection extends BaseMySqlConnection
{
    use Concerns\HasLabels;
    use Concerns\HasDatabaseManager;
    use Concerns\TracksState;

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return ($this->getConfig('name'))
            ? (string) $this->getConfig('name')
            : null;
    }
}
