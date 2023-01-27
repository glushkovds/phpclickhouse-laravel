<?php

namespace PhpClickHouseLaravel\Exceptions;


class QueryException extends \ClickHouseDB\Exception\QueryException
{

    public static function cannotUpdateEmptyValues(): self
    {
        return new self('Error updating empty values');
    }

}
