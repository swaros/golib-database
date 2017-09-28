<?php
namespace golibdatabase\Database\MySql;
use golibdatabase\Database\ConnectData;

/**
 * Description of Connection
 *
 * @author tziegler
 */
class ConnectInfo implements ConnectData{

    private $host = NULL;

    private $userName = NULL;

    private $password = NULL;

    private $shema = NULL;

    public function __construct($username, $password, $host, $shema) {
        $this->userName = $username;
        $this->password = $password;
        $this->host = $host;
        $this->shema = $shema;
    }

    /**
     * get the Hostname
     * @return string
     */
    public function getHost() {
        return $this->host;
    }

    /**
     * get the Password
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * get the used Database name (Shema)
     * @return string
     */
    public function getShemaName() {
        return $this->shema;
    }

    /**
     * get the Username
     * @return string
     */
    public function getUserName() {
        return $this->userName;
    }


}
