<?php


namespace PhpClickHouseLaravel;


use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Fluent;

class SchemaGrammar extends Grammar
{

    /**
     * Compile the query to determine the list of tables.
     *
     * @return string
     */
    public function compileTableExists()
    {
        return "select * from system.tables where database = :0 and name = :1";
    }

    /**
     * Compile a create table command.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @param \Illuminate\Support\Fluent $command
     * @param \Illuminate\Database\Connection $connection
     * @return array
     */
    public function compileCreate(Blueprint $blueprint, Fluent $command, Connection $connection)
    {
        $sql = "CREATE TABLE :table (
                :columns
            )
            ENGINE = MergeTree()
            ORDER BY (:orderBy)";
        $orderBy = $blueprint->getAddedColumns()[0]->name;
        $bindings = [
            ':table' => $blueprint->getTable(),
            ':columns' => implode(",\n", $this->getColumns($blueprint)),
            ':orderBy' => $orderBy,
        ];
        $sql = str_replace(array_keys($bindings), array_values($bindings), $sql);
        return [$sql];
    }

    /**
     * Compile the blueprint's column definitions.
     *
     * @param \Illuminate\Database\Schema\Blueprint $blueprint
     * @return array
     */
    protected function getColumns(Blueprint $blueprint)
    {
        $columns = [];

        foreach ($blueprint->getAddedColumns() as $column) {
            $sql = $column->name . ' ' . $this->getType($column);
            $columns[] = $sql;
        }

        return $columns;
    }

    /**
     * Create the column definition for an integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeInteger(Fluent $column)
    {
        return 'Int32';
    }

    /**
     * Create the column definition for a big integer type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeBigInteger(Fluent $column)
    {
        return 'Int64';
    }

    /**
     * Create the column definition for a string type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeString(Fluent $column)
    {
        return 'String';
    }

    /**
     * Create the column definition for a timestamp type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeTimestamp(Fluent $column)
    {
        return 'DateTime';
    }

    /**
     * Create the column definition for a text type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeText(Fluent $column)
    {
        return 'String';
    }

    /**
     * Create the column definition for a long text type.
     *
     * @param \Illuminate\Support\Fluent $column
     * @return string
     */
    protected function typeLongText(Fluent $column)
    {
        return 'String';
    }

}
