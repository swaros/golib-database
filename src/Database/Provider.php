<?php

namespace golibdatabase\Database;

/**
 *
 * @author tziegler
 */
interface Provider {
    /**
     * @return ConnectData Description
     */
    public function getConnectionData();
    public function getConnection();
}
