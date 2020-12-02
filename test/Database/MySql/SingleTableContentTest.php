<?php

namespace Database\MySql;

use Database\DatabaseTest;
use Database\Mocks\MysqliMock;
use Exception;
use golibdatabase\Database\MySql\SingleTableContent;
use golibdatabase\Database\MySql\TableException;
use golibdatabase\Database\MySql\WhereSet;

require_once __DIR__ . "/../DatabaseTest.php";

class SingleTableContentTest extends DatabaseTest
{
    public function testSingleTable()
    {
        $test = new TestSingleTable();
        $this->assertEquals("testTable", $test->getTableName());
    }

    /**
     * @throws TableException
     */
    public function testGetTestData()
    {
        $test = new TestSingleTable();
        $mockMysqli = new MysqliMock();

        $mysql = $this->getMysqlWithMocks($mockMysqli);

        $resultData = [
            [
                "userId" => 1000,
                "name" => "klaus",
            ]
        ];

        $this->createFetchArrayResult($mockMysqli, "SELECT * FROM testTable", $resultData);

        $ok = $test->getData($mysql);
        $this->assertTrue($ok);
        $this->assertEquals(1000, $test->userId);

    }

    /**
     * @throws TableException
     */
    public function testGetToMuchTestData()
    {
        $test = new TestSingleTable();
        $mockMysqli = new MysqliMock();

        $mysql = $this->getMysqlWithMocks($mockMysqli);
        $resultData = [
            [
                "userId" => 1000,
                "name" => "klaus",
            ],
            [
                "userId" => 2000,
                "name" => "robert"
            ]
        ];

        $this->createFetchArrayResult($mockMysqli, "SELECT * FROM testTable", $resultData);
        $this->expectException(TableException::class);

        $test->getData($mysql);


    }

    /**
     * @throws TableException
     * @throws Exception
     */
    public function testGetTestDataWithWhere()
    {
        $test = new TestSingleTable(new WhereSet());
        $mockMysqli = new MysqliMock();

        $mysql = $this->getMysqlWithMocks($mockMysqli);

        $resultData = [
            [
                "userId" => 1000,
                "name" => "klaus",
            ]
        ];

        $this->createFetchArrayResult($mockMysqli, "SELECT * FROM testTable WHERE (`userId` = '1000')", $resultData);

        $test->getCurrentWhereSet()->isEqual("userId", 1000);
        $ok = $test->getData($mysql);
        $this->assertTrue($ok);
        $this->assertEquals(1000, $test->userId);
        $this->assertEquals('klaus', $test->name);

    }

    /**
     * @throws TableException
     * @throws Exception
     */
    public function testGetTestDataWithWhereAndUpdate()
    {
        $test = new TestSingleTable(new WhereSet());
        $mockMysqli = new MysqliMock();

        $mysql = $this->getMysqlWithMocks($mockMysqli);

        $resultData = [
            [
                "userId" => 8888,
                "name" => "manfred",
            ]
        ];

        $this->createFetchArrayResult(
            $mockMysqli,
            "SELECT * FROM testTable WHERE (`userId` = '8888')",
            $resultData
        );
        $this->createUpdateResult(
            $mockMysqli,
            "UPDATE testTable SET `userId` = '8888',`name` = 'luis' WHERE (`userId` = '8888' AND `name` = 'manfred' AND (`userId` = '8888'))",
            1
        );

        $test->getCurrentWhereSet()->isEqual("userId", 8888);

        $this->assertTrue(
            $test->getData($mysql)
        );
        $this->assertEquals(8888, $test->userId);
        $this->assertEquals('manfred', $test->name);

        $test->name = 'luis';
        $this->assertTrue(
            $test->updateTable($mysql)
        );

    }
}

###### test class #####

class TestSingleTable extends SingleTableContent
{

    public int $userId = 0;
    public string $name = 'han solo';

    function init()
    {
        // TODO: Implement init() method.
    }

    function getTableName()
    {
        return "testTable";
    }
}