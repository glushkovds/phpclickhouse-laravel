<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Closure;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use PhpClickHouseLaravel\Exceptions\QueryException;
use Tinderbox\ClickhouseBuilder\Query\BaseBuilder;

class Builder extends BaseBuilder
{

    use WithClient;
    use BuilderMethodsFromLaravel;

    /** @var string */
    protected $tableSources;
    /** @var Client */
    protected $client;
    protected $settings = [];

    /**
     * The name of the database connection to use.
     *
     * @var string|null
     */
    protected $connection = Connection::DEFAULT_NAME;

    public function __construct(Client $client = null)
    {
        $this->grammar = new Grammar();
        $this->client = $client ?? $this->getThisClient();
    }

    /**
     * Set the SETTINGS clause for the SELECT statement.
     * @link https://clickhouse.com/docs/en/sql-reference/statements/select#settings-in-select-query
     * @param array $settings For example: [max_threads => 3]
     * @return $this
     */
    public function settings(array $settings): self
    {
        $this->settings = $settings;
        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return Statement
     */
    public function get(array $bindings = []): Statement
    {
        return $this->client->select($this->toSql(), $bindings);
    }

    /**
     * @return array
     */
    public function getRows(array $bindings = []): array
    {
        return $this->get($bindings)->rows();
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     */
    public function chunk(int $count, callable $callback): void
    {
        $offset = 0;
        do {
            $rows = $this->limit($count, $offset)->getRows();
            $callback($rows);
            $offset += $count;
        } while ($rows);
    }

    /**
     * For delete query
     * @param string $table
     * @return $this
     */
    public function setSourcesTable(string $table): self
    {
        $this->tableSources = $table;

        return $this;
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function delete(): Statement
    {
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $sql = "ALTER TABLE $table DELETE " . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

    /**
     * Note! This is a heavy operation not designed for frequent use.
     * @return Statement
     */
    public function update(array $values): Statement
    {
        if (empty($values)) {
            throw QueryException::cannotUpdateEmptyValues();
        }
        $table = $this->tableSources ?? $this->getFrom()->getTable();
        $set = [];
        foreach ($values as $key => $value) {
            $set[] = "`$key` = " . $this->grammar->wrap($value);
        }
        $sql = "ALTER TABLE $table UPDATE " . implode(', ', $set) . ' '
            . $this->grammar->compileWheresComponent($this, $this->getWheres());
        return $this->client->write($sql);
    }

    public function newQuery(): self
    {
        return new static($this->client);
    }

    public function count(): int
    {
        $builder = $this->getCountQuery();
        $result = $builder->getRows();

        return (int) $result[0]['count'] ?? 0;
    }

    /**
     * Paginate the given query.
     *
     * @param  int|null|\Closure  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @param  \Closure|int|null  $total
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $count = (clone $this)->count();

        $perPage = ($perPage instanceof Closure
            ? $perPage($count)
            : $perPage
        ) ?: 15;

        $results = $this->limit($perPage, $perPage * ($page - 1))
            ->getRows();

        return new LengthAwarePaginator(
            $results,
            $count,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int|null  $perPage
     * @param  array|string  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: 15;

        // Next we will set the limit and offset for this query so that when we get the
        // results we get the proper section of results. Then, we'll create the full
        // paginator instances for these results with the given page and per page.
        $results = $this->limit($perPage + 1, ($page - 1) * $perPage)
            ->getRows();

        return new Paginator(
            $results,
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ],
        );
    }

}
