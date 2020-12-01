<?php

namespace golibdatabase\Database\MySql;

use golibdatabase\Database\ConnectData;

/**
 * Description of Connection
 *
 * @author tziegler
 */
class ConnectInfo implements ConnectData
{

    private ?string $host;

    private ?string $userName;

    private ?string $password;

    private ?string $schema;

    public function __construct(string $username, string $password, string $host, string $schema)
    {
        $this->userName = $username;
        $this->password = $password;
        $this->host = $host;
        $this->schema = $schema;
    }

    /**
     * get the Hostname
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * get the Password
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * get the used Database name (Schema)
     * @return string
     */
    public function getShemaName()
    {
        return $this->schema;
    }

    /**
     * get the Username
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }


}
