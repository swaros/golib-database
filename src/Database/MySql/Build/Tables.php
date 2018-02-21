<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:40
 */

namespace golibdatabase\Database\MySql\Build;


use golibdatabase\Database\MySql;

/**
 * Class Tables
 * @package golibdatabase\Database\MySql\Build
 */
class Tables
{
    /**
     * @var MySql
     */
    private $connection = NULL;

    /**
     * @var FieldProp[]
     */
    private $tables = array();

    /**
     * @var Fields|null
     */
    private $fields = NULL;

    /**
     * Tables constructor.
     * @param MySql $db
     */
    public function __construct(MySql $db)
    {
        $this->connection = $db;
        $this->fields = new Fields($this->connection);
        $this->fetchTables();
    }

    /**
     * fetch all the tableNames from database
     */
    private function fetchTables() {
        $sql = "SHOW TABLES";
        $tab = $this->connection->select($sql);
        $tables = $tab->getResult();
        $this->tables = array();
        if (is_array($tables)){
            foreach ($tables as $tableName){
                $tabRealname = current($tableName);
                $this->tables[$tabRealname] = $this->fields->getFields($tabRealname);
            }
        }

    }

    /**
     * @return array
     */
    public function getTables(){
        return $this->tables;
    }
}