<?php


namespace SqlQueryWrapper\Plugins;


use SqlQuery\SqlQueryInterface;

abstract class SqlQueryWrapperBasePlugin implements SqlQueryPluginInterface
{

    protected $model;

    public function __construct()
    {
        $this->model = [];
    }


    public static function create()
    {
        return new static();
    }

    public function prepareQuery(SqlQueryInterface $sqlQuery)
    {

    }

    public function prepareModel(int $nbItems, array $rows)
    {

    }

    public function getModel(): array
    {
        return $this->model;
    }

}