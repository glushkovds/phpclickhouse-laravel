<?php

namespace PhpClickHouseLaravel;

class Statement extends \ClickHouseDB\Statement
{
    public function all(): array
    {
        return $this->rows();
    }
}