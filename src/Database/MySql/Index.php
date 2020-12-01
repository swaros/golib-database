<?php

namespace golibdatabase\Database\MySql;

use Exception;
use golibdatabase\Database\MySql;

/**
 * Description of Index
 *
 * @author tziegler
 */
class Index
{

    private static array $indices = array();
    private static array $primaries = array();
    private static array $isRead = array();

    private ?string $currentTable = NULL;

    public function existsTableIndex($tableName): bool
    {
        return !empty(self::$isRead) && isset(self::$isRead[$tableName]);
    }

    /**
     *
     * @return IndexSet[]|bool
     * @throws Exception
     */
    public function getPrimary(): array|bool
    {
        if (!isset(self::$primaries[$this->getCurrentTableName()])) {
            return false;
        }
        return self::$primaries[$this->getCurrentTableName()];
    }

    /**
     *
     * @return bool|IndexSet
     * @throws Exception
     */
    public function getFirstPrimary() : IndexSet|bool
    {
        if (!isset(self::$primaries[$this->getCurrentTableName()])) {
            return false;
        }
        return self::$primaries[$this->getCurrentTableName()][0];
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getPrimaryCount(): int
    {
        if (!isset(self::$primaries[$this->getCurrentTableName()])) {
            return 0;
        }
        return count(self::$primaries[$this->getCurrentTableName()]);
    }

    /**
     * @param MySql $db
     * @throws Exception
     */
    public function registerTableIndex(MySql $db)
    {
        $sql = "show index from {$this->getCurrentTableName()}";
        $res = $db->select($sql);

        if ($res->getError() === NULL) {
            foreach ($res->getResult() as $result) {
                $data = new IndexSet($result);
                self::$isRead[$data->Table] = true;
                self::$indices[$data->Table][] = $data;
                if ($data->Key_name === 'PRIMARY') {
                    self::$primaries[$data->Table][] = $data;
                }
            }
        }
    }

    /**
     * @return string|null
     * @throws Exception
     */
    public function getCurrentTableName()
    {
        if ($this->currentTable === NULL) {
            throw new Exception("Current Table-Name is not set");
        }
        return $this->currentTable;
    }

    public function setCurrentTable($tableName)
    {
        $this->currentTable = $tableName;
    }


}
