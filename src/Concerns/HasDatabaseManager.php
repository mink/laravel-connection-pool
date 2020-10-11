<?php

declare(strict_types=1);

namespace X\LaravelConnectionPool\Concerns;

use X\LaravelConnectionPool\DatabaseManager;
use X\LaravelConnectionPool\MySqlConnection;

trait HasDatabaseManager
{
    /**
     * The database manager instance.
     *
     * @var DatabaseManager|null
     */
    protected ?DatabaseManager $databaseManager;

    /**
     * Gets the database manager if present.
     *
     * @return DatabaseManager|null
     */
    public function getDatabaseManager(): ?DatabaseManager
    {
        return $this->databaseManager;
    }

    /**
     * Sets the database manager.
     *
     * @param DatabaseManager $databaseManager
     * @return MySqlConnection
     */
    public function setDatabaseManager(DatabaseManager $databaseManager): self
    {
        $this->databaseManager = $databaseManager;
        return $this;
    }
}
