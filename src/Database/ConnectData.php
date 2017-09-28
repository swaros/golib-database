<?php
namespace golibdatabase\Database;

/**
 * Interface to set Up Database Connections
 *
 * @author tziegler
 */
interface ConnectData {

    public function getUserName();

    public function getPassword();

    public function getHost();

    public function getShemaName();

}
