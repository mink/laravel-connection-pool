<?php

namespace X\LaravelSwoolePool\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Support\Collection;

class Builder extends BaseBuilder
{
    public function get($columns = ['*']): Collection
    {
        $query = parent::get($columns);

        $this->getConnection()->active(false);

        return $query;
    }
}
