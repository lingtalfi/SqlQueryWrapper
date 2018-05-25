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

    /**
     * This variable is only set once you've called the prepare method.
     */
    private $nbItems;


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
        // READ THE QUERY
        //--------------------------------------------
        foreach ($this->plugins as $plugin) {
            $plugin->onQueryReady($this->sqlQuery);
        }


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
//        az(__FILE__, $qCount, $markers);
        $nbItems = QuickPdo::fetch($qCount, $markers, \PDO::FETCH_COLUMN);
        $this->nbItems = $nbItems;


        // first the rows query
        $q = $this->sqlQuery->getSqlQuery();
//        az(__FILE__, $q, $markers);
        $rows = QuickPdo::fetchAll($q, $markers);
//        az($rows);
        if ($this->rowDecorator) {
            foreach ($rows as $k => $row) {
                call_user_func_array($this->rowDecorator, [&$row]);
                $rows[$k] = $row;
            }
        }
//        az($rows);
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

    public function getNumberOfItems()
    {
        return (int)$this->nbItems;
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