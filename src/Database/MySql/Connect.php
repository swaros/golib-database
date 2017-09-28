<?php
namespace golibdatabase\Database\MySql;
use golibdatabase\Database;
/**
 * Description of Connect
 *
 * @author tziegler
 */
class Connect implements Database\Connect{

    /**
     *
     * @var boolean
     */
    private $connected = false;

    /**
     *
     * @var Database\ConnectData
     */
    private $connctInfo = NULL;

    /**
     *
     * @var \mysqli
     */
    private $mysqli = NULL;

    private $lastError = NULL;

    /**
     * VConnect to Database by using the connection Info
     * @param \golibdatabase\Database\ConnectData $connectionInfo
     * @return boolean
     */
    public function connect(Database\ConnectData $connectionInfo) {
        $this->connctInfo = $connectionInfo;
        return $this->connectDb();
    }

    /**
     * retuns if the Databse is connected
     * @return boolean
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * returns the last error Message
     * @return string
     */
    public function getLastError(){
        return $this->lastError;
    }

    /**
     * get the connection
     * @return \mysqli
     */
    public function getConnection(){
        if ($this->isConnected()){
            return $this->mysqli;
        }

        return NULL;
    }

    /**
     * close the connection
     */
    public function close(){
        if ($this->mysqli !== NULL ){
            $this->mysqli->close();
            $this->connected = false;
        }
    }

    /**
     * connect to database
     * @return boolean
     */
    private function connectDb(){
        $this->mysqli = new \mysqli(
                $this->connctInfo->getHost(),
                $this->connctInfo->getUserName(),
                $this->connctInfo->getPassword(),
                $this->connctInfo->getShemaName());

        if ($this->mysqli->connect_error){
            $this->lastError = $this->mysqli->connect_error;
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

}
