<?php
namespace golibdatabase\Database;

use Exception;

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
     * storage for all connection Providers
     * @var Provider[]
     */
    private static array $connectionStorage = array();

    /**
     * Register a Connection
     * @param Provider $connection
     * @param bool $allowOverWrite
     * @throws Exception
     */
    public function registerConnection(Provider $connection,bool $allowOverWrite = false){
        $storedCon = $this->getStoredConnection($connection->getConnectionData());
        if ($storedCon != NULL && $allowOverWrite === false){
            throw new Exception("Not allowed to overwrite existing connections");
        }

        $id = $this->getStorageId($connection->getConnectionData());
        self::$connectionStorage[$id] = $connection;
    }


    /**
     * creates storage id by connectionData
     * @param ConnectData $conSetup
     * @return string
     */
    private function getStorageId(ConnectData $conSetup): string{
        return $conSetup->getUserName() . '@'
                . $conSetup->getHost() . '['
                . $conSetup->getShemaName() . ']';
    }

    /**
     * checks if a connection already stored
     * @param ConnectData $conSetup
     * @return Boolean
     */
    public function connectionIsStored(ConnectData $conSetup): bool{
        return (isset(self::$connectionStorage[ $this->getStorageId( $conSetup ) ]));
    }

    /**
     * get stored connection or NULL if not stored
     * @param ConnectData $conSetup
     * @return Provider|null
     */
    public function getStoredConnection(ConnectData $conSetup): Provider|null{
        $id = $this->getStorageId($conSetup);
        if (isset(self::$connectionStorage[$id])){
            return self::$connectionStorage[$id];
        }
        return null;
    }

}
