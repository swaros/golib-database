<?php
namespace golibdatabase\Database\MySql;
use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\IndexSet;

/**
 * Description of Index
 *
 * @author tziegler
 */
class Index {

    private static $indecies = array();
    private static $primaries = array();
    private static $isRead = array();

    private $currentTable = NULL;

    public function existsTableIndex($tableName){
        return !empty(self::$isRead) && isset(self::$isRead[$tableName]);
    }

    /**
     *
     * @return IndexSet[]
     */
    public function getPrimary(){
        if (!isset(self::$primaries[$this->getCurrentTableName()])){
            return false;
        }
        return self::$primaries[$this->getCurrentTableName()];
    }

    /**
     *
     * @return IndexSet
     */
    public function getFirstPrimary(){
        if (!isset(self::$primaries[$this->getCurrentTableName()])){
            return false;
        }
        return self::$primaries[$this->getCurrentTableName()][0];
    }

    public function getPrimaryCount(){
        if (!isset(self::$primaries[$this->getCurrentTableName()])){
            return 0;
        }
        return count(self::$primaries[$this->getCurrentTableName()]);
    }

    public function registerTableIndex(MySql $db){
        $sql = "show index from {$this->getCurrentTableName()}";
        $res = $db->select($sql);

        if ($res->getError() === NULL){
            foreach ($res->getResult() as $result){
                $data = new IndexSet($result);
                self::$isRead[$data->Table] = true;
                self::$indecies[$data->Table][] = $data;
                if ($data->Key_name === 'PRIMARY'){
                    self::$primaries[$data->Table][] = $data;
                }
            }
        }
    }

    public function getCurrentTableName(){
        if ($this->currentTable === NULL){
            throw new \Exception("Current Tablename is not set");
        }
        return $this->currentTable;
    }

    public function setCurrentTable($tableName){
        $this->currentTable = $tableName;
    }


}
