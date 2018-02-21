<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:39
 */

namespace golibdatabase\Database\MySql\Build;


use golibdatabase\Database\MySql;

/**
 * Class Classes
 * @package golibdatabase\Database\MySql\Build
 */
class Classes
{
    /**
     * @var MySql|null
     */
    private $connection = null;
    /**
     * @var Tables|null
     */
    private $tableScanner = null;

    /**
     * Classes constructor.
     * @param MySql $db
     */
    public function __construct(MySql $db)
    {
        $this->connection = $db;
        $this->tableScanner = new Tables($this->connection);
    }

    /**
     * @param \Closure $function
     */
    public function exec(\Closure $function ) {
        $tables = $this->tableScanner->getTables();

        foreach ($tables as $tableName => $fields){
            $function($tableName, $fields);
        }
    }
}