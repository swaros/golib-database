<?php
namespace golibdatabase\Database;

/**
 * Description of ConnectManager
 *
 * @author tziegler
 *
 * Storage for connections.
 * Can be used to make sure
 * to use a single instance of connection
 * 
 */

class ConnectManager {

    /**
     * storage for allonnection Provider
     * @var Array[Provider]
     */
    private static $connectionStorage = array();

    /**
     * Register a Connection
     * @param \golibdatabase\Database\Provider $connection
     * @param type $allowOverWrite
     * @throws \Exception
     */
    public function registerConnection(Provider $connection, $allowOverWrite = false){
        $storedCon = $this->getStoredConnection($connection->getConnectionData());
        if ($storedCon != NULL && $allowOverWrite === false){
            throw new \Exception("Not allowed to overwrite existing connections");
        }

        $id = $this->getStorageId($connection->getConnectionData());
        self::$connectionStorage[$id] = $connection;
    }


    /**
     * creates storage id by connectionData
     * @param \golibdatabase\Database\ConnectData $conSetup
     * @return string
     */
    private function getStorageId(ConnectData $conSetup){
        return $conSetup->getUserName() . '@'
                . $conSetup->getHost() . '['
                . $conSetup->getShemaName() . ']';
    }

    /**
     * checks if a connection already stored
     * @param \golibdatabase\Database\ConnectData $conSetup
     * @return Boolean
     */
    public function connectionIsStored(ConnectData $conSetup){
        return (isset(self::$connectionStorage[ $this->getStorageId( $conSetup ) ]));
    }

    /**
     * get stored connection or NULL if not stored
     * @param \golibdatabase\Database\ConnectData $conSetup
     * @return Provider
     */
    public function getStoredConnection(ConnectData $conSetup){
        $id = $this->getStorageId($conSetup);
        if (isset(self::$connectionStorage[$id])){
            return self::$connectionStorage[$id];
        }
        return null;
    }

}
