<?php

namespace Database;

use Database\Mocks\MysqliMock;
use Exception;
use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\ConnectInfo;
use mysqli_result;

require_once "DatabaseTest.php";

class MySqlTest extends DatabaseTest
{
    /**
     * @throws Exception
     */
    public function testConnect() {
        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($this->getMysqliMock());

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $resCon = $mySql->getConnection();
        $this->assertInstanceOf(MysqliMock::class, $resCon);
    }

    /**
     * @throws Exception
     */
    public function testConnectFail() {
        $this->expectExceptionMessage("Can't connect to Database: because of tests");
        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;
        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(false);

        $conMock->expects($this->atLeastOnce())
            ->method("getLastError")
            ->willReturn(" because of tests");


        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $resCon = $mySql->getConnection();
        $this->assertInstanceOf(MysqliMock::class, $resCon);
    }

    /**
     * @throws Exception
     */
    public function testBase() {
        $this->checkMysqli();

        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $fakeMysqli = $this->getMysqliMock();
        $fakeMysqli->setQueryResult($result);
        $fakeMysqli->errno = 0;
        $fakeMysqli->affected_rows = 10;
        $fakeMysqli->insert_id = 777;

        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $set = $mySql->select('select ?', 1);
        $this->assertNotNull($set);
        $this->assertInstanceOf(MySql\ResultSet::class, $set);
        $this->assertEquals(0, $set->count());
        $this->assertEquals(0, $set->getErrorNr());
        $this->assertEquals(10, $mySql->getlastAffectedRows());
        $this->assertEquals(777, $mySql->getLastInsertId());
    }

    /**
     * @throws Exception
     */
    public function testQueryFail() {
        $this->checkMysqli();
        $this->expectError();
        $this->expectErrorMessage("TEST CASE ERROR");
        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $fakeMysqli = $this->getMysqliMock();
        $fakeMysqli->setQueryResult($result);
        $fakeMysqli->errno = 1;
        $fakeMysqli->error = "TEST CASE ERROR";


        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $mySql->query('select 1');

    }

    /**
     * @throws Exception
     */
    public function testTransaction() {
        $this->checkMysqli();

        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $fakeMysqli = $this->getMysqliMock();
        $fakeMysqli->setQueryResult($result);
        $fakeMysqli->errno = 0;
        $fakeMysqli->error = "";


        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);


        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $mySql->begin_transaction();
        $this->assertArrayHasKey("SET autocommit=0", $fakeMysqli->queriesAsKey);
        $this->assertArrayHasKey("START TRANSACTION", $fakeMysqli->queriesAsKey);

        $mySql->select('just-testing checkBase where ? = ?',"a","b");
        $this->assertArrayHasKey("just-testing checkBase where 'a' = 'b'", $fakeMysqli->queriesAsKey);

        $this->assertTrue($mySql->inTransaction());

        $mySql->commit();
        $this->assertFalse($mySql->inTransaction());

        $this->assertArrayHasKey("COMMIT", $fakeMysqli->queriesAsKey);
        $this->assertArrayHasKey("SET autocommit=1", $fakeMysqli->queriesAsKey);

    }

    /**
     * @throws Exception
     */
    public function testTransactionRollBack() {
        $this->checkMysqli();

        $result = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $conMock = $this->getMockBuilder(MySql\Connect::class)
            ->getMock()
        ;

        $fakeMysqli = $this->getMysqliMock();
        $fakeMysqli->setQueryResult($result);
        $fakeMysqli->errno = 0;
        $fakeMysqli->error = "";


        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMysqli);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);


        $con = new ConnectInfo("user","pw","somewhere","check");
        $mySql = new MySql($con, $conMock);
        $mySql->begin_transaction();
        $this->assertArrayHasKey("SET autocommit=0", $fakeMysqli->queriesAsKey);
        $this->assertArrayHasKey("START TRANSACTION", $fakeMysqli->queriesAsKey);

        $mySql->select('just-testing checkBase where ? = ?',"a","b");
        $this->assertArrayHasKey("just-testing checkBase where 'a' = 'b'", $fakeMysqli->queriesAsKey);

        $this->assertTrue($mySql->inTransaction());

        $mySql->rollback();
        $this->assertFalse($mySql->inTransaction());

        $this->assertArrayHasKey("ROLLBACK", $fakeMysqli->queriesAsKey);
        $this->assertArrayHasKey("SET autocommit=1", $fakeMysqli->queriesAsKey);

    }
}
