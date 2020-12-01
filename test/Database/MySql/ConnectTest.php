<?php

namespace Database\MySql;

use golibdatabase\Database\MySql\Connect;
use golibdatabase\Database\MySql\ConnectInfo;
use mysqli;
use PHPUnit\Framework\TestCase;

class ConnectTest extends TestCase
{

    private function checkMysqli() {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
                'The MySQLi extension is not available.'
            );
        }
    }



    public function testGetConnection()
    {
        $this->checkMysqli();
        $mysqli = $this->getMockBuilder(mysqli::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableAutoload()
            ->getMock()
        ;
        $mysqli->expects($this->atLeast(2))
            ->method('close');
        $mysqli->expects($this->once())
            ->method('connect')
            ->with("localhost","user","password","database");

        $conInfo = new ConnectInfo("user","password","localhost","database");
        $connection = new Connect();
        $this->assertTrue($connection->connect($conInfo, $mysqli));
        $this->assertTrue($connection->isConnected());

        $this->assertInstanceOf(get_class($mysqli),$connection->getConnection());

        $connection->close();
        $this->assertFalse($connection->isConnected());
        $this->assertNull($connection->getConnection());

    }


}
