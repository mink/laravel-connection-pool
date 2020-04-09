<?php

namespace X\LaravelSwoolePool;

use X\LaravelSwoolePool\Query\Builder;
use Illuminate\Database\MySqlConnection as BaseMySqlConnection;

class MySqlConnection extends BaseMySqlConnection
{
    public bool $active = false;

    public function query(): Builder
    {
        return new Builder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor()
        );
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function active(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}
