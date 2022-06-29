<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use Illuminate\Database\Schema\Builder as BaseBuilder;

class SchemaBuilder extends BaseBuilder
{
    /** @inheritDoc */
    public function hasTable($table): bool
    {
        return count($this->connection->select(
                $this->grammar->compileTableExists(), [$this->connection->getDatabaseName(), $table]
            )) > 0;
    }
}
