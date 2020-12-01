<?php

namespace golibdatabase\Database\MySql;

use golibdatabase\Database;
use golibdatabase\Database\ConnectData;
use mysqli;

/**
 * Description of Connect
 *
 * @author tziegler
 */
class Connect implements Database\Connect
{

    /**
     *
     * @var boolean
     */
    private bool $connected = false;

    /**
     *
     * @var ConnectData|null
     */
    private ?ConnectData $connectInfo = NULL;

    /**
     *
     * @var mysqli|null
     */
    private ?mysqli $mysqli = NULL;

    private ?string $lastError = NULL;

    /**
     * Connect to Database by using the connection Info.
     * a created mysqli object can be injected.
     * this connection will be closed and reopened by the
     * connect command.
     * so this is not useful for production to inject a
     * established mysqli connection with different credentials.
     * injecting a already created mysqli object
     * is meant for testing not for production.
     *
     * @param ConnectData $connectionInfo
     * @param mysqli|null $mysqli
     * @return boolean
     */
    public function connect(ConnectData $connectionInfo, mysqli|null $mysqli = null): bool
    {
        if ($mysqli !== null) {
            $this->mysqli = $mysqli;
        }
        $this->connectInfo = $connectionInfo;
        return $this->connectDb();
    }

    /**
     * returns if the Database is connected
     * @return boolean
     */
    public function isConnected()
    {
        return $this->connected;
    }

    /**
     * returns the last error Message
     * @return string
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * get the connection
     * @return mysqli
     */
    public function getConnection()
    {
        if ($this->isConnected()) {
            return $this->mysqli;
        }

        return NULL;
    }

    /**
     * close the connection
     */
    public function close()
    {
        if ($this->mysqli !== NULL) {
            $this->mysqli->close();
            $this->connected = false;
        }
    }

    /**
     * connect to database by using the submitted
     * connection info.
     * if a mysqli was injected, then the connection
     * will be closed and reopened also by using
     * the connectionInfo.
     * injecting a already created mysqli object
     * i meant for testing not for production
     * @return boolean
     */
    private function connectDb(): bool
    {
        if ($this->mysqli == null) {
            $this->mysqli = new mysqli(
                $this->connectInfo->getHost(),
                $this->connectInfo->getUserName(),
                $this->connectInfo->getPassword(),
                $this->connectInfo->getShemaName());
        } else {
            // reconnect
            $this->mysqli->close();
            $this->mysqli->connect(
                $this->connectInfo->getHost(),
                $this->connectInfo->getUserName(),
                $this->connectInfo->getPassword(),
                $this->connectInfo->getShemaName()
            );
        }
        if ($this->mysqli->connect_error) {
            $this->lastError = $this->mysqli->connect_error;
            $this->connected = false;
            return false;
        }

        $this->connected = true;
        return true;
    }

}
