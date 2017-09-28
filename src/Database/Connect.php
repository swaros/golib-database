<?php
namespace golibdatabase\Database;

/**
 *
 * @author tziegler
 */
interface Connect {
    public function connect(ConnectData $connectionInfo);
    /**
     * return true if connected
     * @return boolean
     */
    public function isConnected();

}
