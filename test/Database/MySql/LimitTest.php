<?php

namespace Database\MySql;

use golibdatabase\Database\MySql\Limit;
use PHPUnit\Framework\TestCase;

class LimitTest extends TestCase
{

    public function testIsUsable()
    {
        $limit = new Limit();
        $limit->count = 10;
        $this->assertTrue($limit->isUsable());
    }

    public function testGetLimitStr()
    {
        $limit = new Limit();
        $limit->count = 10;
        $limit->start = 20;
        $this->assertEquals("LIMIT 20,10", $limit->getLimitStr());

        $limit->count = 100;
        $limit->start = 0;
        $this->assertEquals("LIMIT 0,100", $limit->getLimitStr());

        $limit->count = 100;
        $limit->start = null;
        $this->assertEquals("LIMIT 100", $limit->getLimitStr());

        $limit->count = null;
        $this->assertEquals("", $limit->getLimitStr());
    }
}
