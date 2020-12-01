<?php

namespace Database\MySql;

use golibdatabase\Database\MySql\ConnectInfo;
use PHPUnit\Framework\TestCase;

class ConnectInfoTest extends TestCase
{
    public function testPlainUsage()
    {
        $con = new ConnectInfo("user","pw","somewhere","check");
        $this->assertEquals("user", $con->getUserName());
        $this->assertEquals("pw", $con->getPassword());
        $this->assertEquals("somewhere", $con->getHost());
        $this->assertEquals("check", $con->getShemaName());

    }
}
