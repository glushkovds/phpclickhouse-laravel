<?php

declare(strict_types=1);

namespace PhpClickHouseLaravel;

use ClickHouseDB\Client;
use ClickHouseDB\Statement;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Database\Eloquent\Concerns\HidesAttributes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tinderbox\ClickhouseBuilder\Query\Enums\Operator;
use Tinderbox\ClickhouseBuilder\Query\TwoElementsLogicExpression;

class BaseModel
{
    use HasAttributes;
    use HidesAttributes;
    use HasEvents;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Use this only when you have Buffer table engine for inserts
     * @link https://clickhouse.tech/docs/ru/engines/table-engines/special/buffer/
     *
     * @var string
     */
    protected $tableForInserts;

    /**
     * Use this field for OPTIMIZE TABLE OR ALTER TABLE (also DELETE) queries
     *
     * @var string
     */
    protected $tableSources;

    /**
     * Indicates if the model exists.
     *
     * @var bool
     */
    public $exists = false;

    /**
     * The event dispatcher instance.
     *
     * @var Dispatcher
     */
    protected static $dispatcher;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table ?? Str::snake(Str::pluralStudly(class_basename($this)));
    }

    /**
     * Get the table name for insert queries
     *
     * @return string
     */
    public function getTableForInserts(): string
    {
        return $this->tableForInserts ?? $this->getTable();
    }

    /**
     * Use this field for OPTIMIZE TABLE OR ALTER TABLE (also DELETE) queries
     * @return string
     */
    public function getTableSources(): string
    {
        return $this->tableSources ?? $this->getTable();
    }

    /**
     * @return Client
     */
    public static function getClient(): Client
    {
        return DB::connection('clickhouse')->getClient();
    }

    /**
     * Create and return an un-saved model instance.
     * @param array $attributes
     * @return static
     */
    public static function make(array $attributes = [])
    {
        $model = new static;
        $model->fill($attributes);

        return $model;
    }

    /**
     * Save a new model and return the instance.
     * @param array $attributes
     * @return static
     * @throws Exception
     */
    public static function create(array $attributes = [])
    {
        $model = static::make($attributes);
        $model->fireModelEvent('creating', false);

        if ($model->save()) {
            $model->fireModelEvent('created', false);
        }

        return $model;
    }

    /**
     * Fill the model with an array of attributes.
     * @param array $attributes
     * @return $this
     */
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Save the model to the database.
     * @param array $options
     * @return bool
     * @throws Exception
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new Exception("Clickhouse does not allow update rows");
        }
        $this->exists = !static::insertAssoc([$this->getAttributes()])->isError();

        $this->fireModelEvent('saved', false);

        return $this->exists;
    }

    /**
     * Bulk insert into Clickhouse database
     * @param array[] $rows
     * @return Statement
     * @deprecated use insertBulk
     */
    public static function insert(array $rows): Statement
    {
        return static::getClient()->insert((new static)->getTableForInserts(), $rows);
    }

    /**
     * Bulk insert into Clickhouse database
     * @param array[] $rows
     * @param array $columns
     * @return Statement
     * @example MyModel::insertBulk([['model 1', 1], ['model 2', 2]], ['model_name', 'some_param']);
     */
    public static function insertBulk(array $rows, array $columns = []): Statement
    {
        return static::getClient()->insert((new static)->getTableForInserts(), $rows, $columns);
    }

    /**
     * Prepare each row by calling static::prepareFromRequest to bulk insert into database
     * @param array[] $rows
     * @param array $columns
     * @return Statement
     */
    public static function prepareAndInsert(array $rows, array $columns = []): Statement
    {
        $rows = array_map('static::prepareFromRequest', $rows, $columns);

        return static::getClient()
            ->insert((new static)->getTableForInserts(), $rows, $columns);
    }

    /**
     * Bulk insert rows as associative array into Clickhouse database
     * @param array[] $rows
     * @return Statement
     * @example MyModel::insertAssoc([['model_name' => 'model 1', 'some_param' => 1], ['model_name' => 'model 2', 'some_param' => 2]]);
     */
    public static function insertAssoc(array $rows): Statement
    {
        $rows = array_values($rows);
        if (isset($rows[0]) && isset($rows[1])) {
            $keys = array_keys($rows[0]);
            foreach ($rows as &$row) {
                $row = array_replace(array_flip($keys), $row);
            }
        }
        return static::getClient()->insertAssocBulk((new static)->getTableForInserts(), $rows);
    }

    /**
     * Prepare each row by calling static::prepareAssocFromRequest to bulk insert into database
     * @param array[] $rows
     * @return Statement
     */
    public static function prepareAndInsertAssoc(array $rows): Statement
    {
        $rows = array_map('static::prepareAssocFromRequest', $rows);
        return static::insertAssoc($rows);
    }

    /**
     * Prepare row to insert into DB, non associative array
     * Need to overwrite in nested models
     * @param array $row
     * @param array $columns
     * @return array
     */
    public static function prepareFromRequest(array $row, array $columns = []): array
    {
        return $row;
    }

    /**
     * Prepare row to insert into DB, associative array
     * Need to overwrite in nested models
     * @param array $row
     * @return array
     */
    public static function prepareAssocFromRequest(array $row): array
    {
        return $row;
    }

    /**
     * @param string|array|RawColumn $select optional = ['*']
     * @return Builder
     */
    public static function select($select = ['*']): Builder
    {
        return (new Builder)->select($select)->from((new static)->getTable());
    }

    /**
     * Necessary stub for HasAttributes trait
     * @return array
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Necessary stub for HasAttributes trait
     * @return bool
     */
    public function usesTimestamps(): bool
    {
        return false;
    }

    /**
     * Necessary stub for HasAttributes trait
     * @param string $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        return null;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set(string $key, $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Optimize table. Using for ReplacingMergeTree, etc.
     * @source https://clickhouse.tech/docs/ru/sql-reference/statements/optimize/
     * @param bool $final
     * @param string|null $partition
     * @return Statement
     */
    public static function optimize(bool $final = false, ?string $partition = null): Statement
    {
        $sql = "OPTIMIZE TABLE " . (new static)->getTableSources();
        if ($partition) {
            $sql .= " PARTITION $partition";
        }
        if ($final) {
            $sql .= " FINAL";
        }

        return static::getClient()->write($sql);
    }

    public static function truncate(): Statement
    {
        return static::getClient()->write('TRUNCATE TABLE ' . (new static())->getTableSources());
    }

    /**
     * @param TwoElementsLogicExpression|string|Closure $column
     * @param string|null $operator
     * @param int|float|string|null $value
     * @param string $concatOperator Operator::AND for example
     * @return Builder
     */
    public static function where($column, $operator = null, $value = null, string $concatOperator = Operator::AND)
    {
        $static = new static;
        $builder = (new Builder)->select(['*'])
            ->from($static->getTable())
            ->setSourcesTable($static->getTableSources());
        if (is_null($value)) {
            // Fix func_num_args() in where clause in BaseBuilder
            $builder->where($column, $operator);
        } else {
            $builder->where($column, $operator, $value, $concatOperator);
        }

        return $builder;
    }

    /**
     * @param string $expression
     * @return Builder
     */
    public static function whereRaw(string $expression):Builder
    {
        $static = new static;

        return (new Builder)->select(['*'])
            ->from($static->getTable())
            ->setSourcesTable($static->getTableSources())
            ->whereRaw($expression);
    }
}
