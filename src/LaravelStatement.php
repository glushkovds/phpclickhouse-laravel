<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Statement;

class LaravelStatement extends Statement
{
    public static function fromStatement(Statement $statement): self
    {
        if ($statement instanceof self) {
            return $statement;
        }

        return new self($statement->getRequest());
    }

    /**
     * Return rows in the same shape as Laravel's query builder collection.
     */
    public function all(): array
    {
        return array_map(static fn (array $row): object => (object) $row, $this->rows());
    }
}
