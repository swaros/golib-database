<?php

namespace Database\MySql;

use Database\DatabaseTest;
use Exception;
use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\ConnectInfo;
use golibdatabase\Database\MySql\Index;
use mysqli_result;

require_once __DIR__ . "/../DatabaseTest.php";

class IndexTest extends DatabaseTest
{
    /**
     * @throws Exception
     */
    public function testBase()
    {

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;


        $indexResult = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();


        $index = [
            "Table" => "test-table",
            "Key_name" => "PRIMARY"
        ];
        $callCount = 0;
        $indexResult->expects($this->atMost(2))
            ->method('fetch_array')
            ->will($this->returnCallback(function ($whatEver) use ($index, &$callCount){
                $callCount++;
                if ($callCount === 1) {
                    return $index;
                }
                return false;
            }));


        $fakeMysqli = $this->getMysqliMock();
        $fakeMysqli->errno = 0;
        $fakeMysqli->affected_rows = 1;
        $fakeMysqli->setQueryResult($indexResult,"show index from test-table");

        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $index = $mySql->getTableIndex("test-table");
        $this->assertArrayHasKey("show index from test-table",$fakeMysqli->queriesAsKey);
        $exists = $index->existsTableIndex("test-table");
        $this->assertTrue($exists);

        $this->assertEquals(1, $index->getPrimaryCount());
        $keys = $index->getFirstPrimary();
        $this->assertEquals("test-table", $keys->Table);


    }
}
