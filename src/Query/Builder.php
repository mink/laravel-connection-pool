<?php

namespace X\LaravelSwoolePool\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

class Builder extends BaseBuilder
{
    public function get($columns = ['*']): Collection
    {
        $this->getConnection()->setActive(true);

        $query = parent::get($columns);

        $this->getConnection()->setActive(false);

        return $query;
    }
}
