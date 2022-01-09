<?php


namespace PhpClickHouseLaravel;


class SchemaBuilder extends \Illuminate\Database\Schema\Builder
{
    /** @inheritDoc */
    public function hasTable($table)
    {
        return count($this->connection->select(
                $this->grammar->compileTableExists(), [$this->connection->getDatabaseName(), $table]
            )) > 0;
    }

}
