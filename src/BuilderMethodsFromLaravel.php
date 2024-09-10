<?php

namespace PhpClickHouseLaravel;

use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Tinderbox\ClickhouseBuilder\Query\Expression;

trait BuilderMethodsFromLaravel
{

    public function useWritePdo()
    {
        return $this;
    }

    /**
     * Get a collection instance containing the values of a given column.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @param string|null $key
     * @return \Illuminate\Support\Collection
     */
    public function pluck($column, $key = null)
    {
        // First, we will need to select the results of the query accounting for the
        // given columns / key. Once we have the results, we will be able to take
        // the results and get the exact data that was requested for the query.
        $queryResult = $this->onceWithColumns(
            is_null($key) ? [$column] : [$column, $key],
            function () {
                return $this->runSelect();
            }
        );

        if (empty($queryResult)) {
            return collect();
        }

        // If the columns are qualified with a table or have an alias, we cannot use
        // those directly in the "pluck" operations since the results from the DB
        // are only keyed by the column itself. We'll strip the table out here.
        $column = $this->stripTableForPluck($column);

        $key = $this->stripTableForPluck($key);

        return is_array($queryResult[0])
            ? $this->pluckFromArrayColumn($queryResult, $column, $key)
            : $this->pluckFromObjectColumn($queryResult, $column, $key);
    }

    /**
     * Strip off the table name or alias from a column identifier.
     *
     * @param string $column
     * @return string|null
     */
    protected function stripTableForPluck($column)
    {
        if (is_null($column)) {
            return $column;
        }

        $columnString = $column instanceof ExpressionContract
            ? $column->getValue(new \Illuminate\Database\Query\Grammars\Grammar())
            : $column;

        $separator = str_contains(strtolower($columnString), ' as ') ? ' as ' : '\.';

        return last(preg_split('~' . $separator . '~i', $columnString));
    }

    /**
     * Retrieve column values from rows represented as objects.
     *
     * @param array $queryResult
     * @param string $column
     * @param string $key
     * @return \Illuminate\Support\Collection
     */
    protected function pluckFromObjectColumn($queryResult, $column, $key)
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row->$column;
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row->$key] = $row->$column;
            }
        }

        return collect($results);
    }

    /**
     * Retrieve column values from rows represented as arrays.
     *
     * @param array $queryResult
     * @param string $column
     * @param string $key
     * @return \Illuminate\Support\Collection
     */
    protected function pluckFromArrayColumn($queryResult, $column, $key)
    {
        $results = [];

        if (is_null($key)) {
            foreach ($queryResult as $row) {
                $results[] = $row[$column];
            }
        } else {
            foreach ($queryResult as $row) {
                $results[$row[$key]] = $row[$column];
            }
        }

        return collect($results);
    }

    /**
     * Execute the given callback while selecting the given columns.
     *
     * After running the callback, the columns are reset to the original value.
     *
     * @param array $columns
     * @param callable $callback
     * @return mixed
     */
    protected function onceWithColumns($columns, $callback)
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $result = $callback();

        $this->columns = $original;

        return $result;
    }

    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect()
    {
        return $this->getRows();
    }


    /**
     * Retrieve the minimum value of a given column.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @return mixed
     */
    public function min($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @return mixed
     */
    public function max($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @return mixed
     */
    public function sum($column)
    {
        $result = $this->aggregate(__FUNCTION__, [$column]);

        return $result ?: 0;
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @return mixed
     */
    public function avg($column)
    {
        return $this->aggregate(__FUNCTION__, [$column]);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param \Illuminate\Contracts\Database\Query\Expression|string $column
     * @return mixed
     */
    public function average($column)
    {
        return $this->avg($column);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions || $this->havings ? [] : ['columns'])
            ->setAggregate($function, $columns)
            ->getRows();

        if ($results) {
            return array_change_key_case((array)$results[0])['aggregate'];
        }
        return null;
    }

    /**
     * Set the aggregate property without running the query.
     *
     * @param string $function
     * @param array $columns
     * @return $this
     */
    protected function setAggregate($function, $columns)
    {
        $this->select(new Expression("$function(" . implode(',', $columns) . ") AS aggregate"));

        return $this;
    }

    public function insert(array $values)
    {
        $table = $this->tableSources ?? (string)$this->getFrom()->getTable();
        $this->client->insertAssocBulk($table, $values);
    }
}