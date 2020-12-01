<?php

namespace Database;

use Exception;
use golibdatabase\Database\ConnectManager;
use golibdatabase\Database\MySql;
use golibdatabase\Database\Provider;
use PHPUnit\Framework\TestCase;

class ConnectManagerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConnectionIsStored()
    {
        $connection = new MySql\ConnectInfo("user", "password", "hostname", "my-database");
        $cnManager = new ConnectManager();
        $db = new MySql($connection);
        $cnManager->registerConnection($db);
        $this->assertTrue($cnManager->connectionIsStored($connection));
    }

    public function testDoubleRegisterFail()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Not allowed to overwrite existing connections");
        $connection = new MySql\ConnectInfo("bbb_user", "password", "hostname", "my-database");
        $cnManager = new ConnectManager();
        $db = new MySql($connection);
        $cnManager->registerConnection($db);
        $cnManager->registerConnection($db);

    }

    /**
     * @throws Exception
     */
    public function testGetStoredConnection()
    {
        $connection = new MySql\ConnectInfo("xx_user", "password", "hostname", "my-database");
        $connection2 = new MySql\ConnectInfo("xx_user2", "password2", "hostname2", "my-database2");
        $connection3 = new MySql\ConnectInfo("xx_user3", "password3", "hostname3", "my-database3");
        $cnManager = new ConnectManager();
        $db = new MySql($connection);
        $db2 = new MySql($connection2);
        $cnManager->registerConnection($db);
        $cnManager->registerConnection($db2);

        $cn = $cnManager->getStoredConnection($connection);
        $this->assertNotNull($cn);
        $this->assertInstanceOf(Provider::class, $cn);
        $this->assertEquals("xx_user", $cn->getConnectionData()->getUserName());
        $this->assertEquals("hostname", $cn->getConnectionData()->getHost());
        $this->assertEquals("password", $cn->getConnectionData()->getPassword());
        $this->assertEquals("my-database", $cn->getConnectionData()->getShemaName());

        $cn2 = $cnManager->getStoredConnection($connection2);
        $this->assertNotNull($cn2);
        $this->assertInstanceOf(Provider::class, $cn2);
        $this->assertEquals("xx_user2", $cn2->getConnectionData()->getUserName());
        $this->assertEquals("hostname2", $cn2->getConnectionData()->getHost());
        $this->assertEquals("password2", $cn2->getConnectionData()->getPassword());
        $this->assertEquals("my-database2", $cn2->getConnectionData()->getShemaName());

        // was not stored
        $cn2 = $cnManager->getStoredConnection($connection3);
        $this->assertNull($cn2);

        // test about control
        $this->assertTrue($cnManager->connectionIsStored($connection));
        $this->assertTrue($cnManager->connectionIsStored($connection2));
        $this->assertFalse($cnManager->connectionIsStored($connection3));
    }

}
