<?php


namespace SqlQueryWrapper;


use QuickPdo\QuickPdo;
use SqlQuery\SqlQueryInterface;
use SqlQueryWrapper\Exception\SqlQueryWrapperException;
use SqlQueryWrapper\Plugins\SqlQueryPluginInterface;

class SqlQueryWrapper implements SqlQueryWrapperInterface
{

    /**
     * @var SqlQueryInterface
     */
    protected $sqlQuery;

    /**
     * @var SqlQueryPluginInterface[]
     */
    protected $plugins;

    /**
     * fn rowDecorator ( array &$row )
     */
    protected $rowDecorator;

    // view
    private $rows;
    private $models;


    public function __construct()
    {
        $this->plugins = [];
    }

    public static function create()
    {
        return new static();
    }


    public function getSqlQuery(): SqlQueryInterface
    {
        return $this->sqlQuery;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function prepare()
    {


        //--------------------------------------------
        // PREPARE THE SQL QUERY
        //--------------------------------------------
        foreach ($this->plugins as $plugin) {
            $plugin->prepareQuery($this->sqlQuery);
        }


        //--------------------------------------------
        // EXECUTE THE SQL QUERY
        //--------------------------------------------
        // first the count query
        $qCount = $this->sqlQuery->getCountSqlQuery();
        $markers = $this->sqlQuery->getMarkers();
//        az(__FILE__, $qCount);
        $nbItems = QuickPdo::fetch($qCount, $markers, \PDO::FETCH_COLUMN);


        // first the rows query
        $q = $this->sqlQuery->getSqlQuery();
        $rows = QuickPdo::fetchAll($q, $markers);
        if ($this->rowDecorator) {
            foreach ($rows as $k => $row) {
                call_user_func_array($this->rowDecorator, [&$row]);
                $rows[$k] = $row;
            }
        }
        $this->rows = $rows;


        //--------------------------------------------
        // PREPARE THE MODELS
        //--------------------------------------------
        foreach ($this->plugins as $plugin) {
            $plugin->prepareModel($nbItems, $rows);
        }

        return $this;
    }

    public function getPlugin(string $name)
    {
        if (array_key_exists($name, $this->plugins)) {
            return $this->plugins[$name];
        }
        return false;
    }


    //--------------------------------------------
    // VIEW METHODS
    //--------------------------------------------
    public function getRows()
    {
        return $this->rows;
    }

    public function getModel(string $pluginName)
    {
        if (array_key_exists($pluginName, $this->plugins)) {
            return $this->plugins[$pluginName]->getModel();
        }
        throw new SqlQueryWrapperException("plugin not set: $pluginName");
    }






    //--------------------------------------------
    //
    //--------------------------------------------
    public function setSqlQuery(SqlQueryInterface $sqlQuery)
    {
        $this->sqlQuery = $sqlQuery;
        return $this;
    }

    public function setPlugin(string $name, SqlQueryPluginInterface $plugin)
    {
        $this->plugins[$name] = $plugin;
        return $this;
    }

    public function setRowDecorator(callable $rowDecorator)
    {
        $this->rowDecorator = $rowDecorator;
        return $this;
    }


}