<?php
/**
 * Created by PhpStorm.
 * User: tziegler
 * Date: 20.02.18
 * Time: 14:42
 */

namespace golibdatabase\Database\MySql\Build;


use golibdatabase\Database\MySql;

/**
 * Class Fields
 * @package golibdatabase\Database\MySql\Build
 */
class Fields
{
    /**
     * @var MySql
     */
    private $connection = NULL;

    /**
     * Fields constructor.
     * @param MySql $db
     */
    public function __construct(MySql $db)
    {
        $this->connection = $db;
    }

    /**
     * @param $tableName
     * @return FieldProp[]|null
     */
    public function getFields($tableName) {
        $sql = 'SHOW COLUMNS FROM `'.$tableName.'`';
        $fields = $this->connection->select($sql);
        if ($fields){
            $fData = array();
            foreach($fields->getResult() as $field){
                $fObj = new FieldProp($field);
                $fData[] = $fObj;
            }
            return $fData;
        }
        return null;
    }
}