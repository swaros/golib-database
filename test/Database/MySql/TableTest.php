<?php

namespace Database\MySql;

use Database\DatabaseTest;
use Exception;
use golib\Types\PropsFactory;
use golibdatabase\Database\MySql\Table;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . "/../DatabaseTest.php";

class TableTest extends DatabaseTest
{

    private array $tableContentTwoUsers = [
        [
            "userID" => 1,
            "money" => 560,
            "name" => 'robert',
        ], [
            "userID" => 2,
            "money" => 9968,
            "name" => 'carol',
        ],
    ];

    /**
     * @throws Exception
     */
    public function testFetchData()
    {
        $table = new TestTable();

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `testUserTable`", $this->tableContentTwoUsers);

        $table->fetchData($mysql);
    }

    /**
     * @throws Exception
     */
    public function testFetchDataAndForeach()
    {
        $table = new TestTable();

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `testUserTable`", $this->tableContentTwoUsers);

        $table->fetchData($mysql);
        $callCounter = 0;
        $table->foreachCall(function (TestTableProp $data) use (&$callCounter) {
            $callCounter++;
            if ($callCounter === 1) {

                $this->assertEquals(1, $data->userID);
                $this->assertEquals(560, $data->money);
                $this->assertEquals("robert", $data->name);
            }
            if ($callCounter === 2) {
                $this->assertEquals(2, $data->userID);
                $this->assertEquals(9968, $data->money);
                $this->assertEquals("carol", $data->name);
            }
        });
    }

    /**
     * @throws Exception
     */
    public function testFetchDataAndNewData()
    {
        $table = new TestTable();

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `testUserTable`", $this->tableContentTwoUsers);

        $table->fetchData($mysql);
        // one way
        $table->registerItem(new TestTableProp("userID",[
            "userID" => 3,
            "money" => 45,
            "name" => 'luis',
        ]));
        // more convenient way
        $newEntry = $table->getPropFactory();
        $newEntry->userID = 336;
        $newEntry->money = 56000;
        $newEntry->name = "sam";
        $table->registerItem($newEntry);

        $table->foreachCall(function (TestTableProp $data) use (&$callCounter) {
            $callCounter++;
            if ($callCounter === 1) {

                $this->assertEquals(1, $data->userID);
                $this->assertEquals(560, $data->money);
                $this->assertEquals("robert", $data->name);
            }
            if ($callCounter === 2) {
                $this->assertEquals(2, $data->userID);
                $this->assertEquals(9968, $data->money);
                $this->assertEquals("carol", $data->name);
            }
            if ($callCounter === 3) {
                $this->assertEquals(3, $data->userID);
                $this->assertEquals(45, $data->money);
                $this->assertEquals("luis", $data->name);
            }
            if ($callCounter === 4) {
                $this->assertEquals(336, $data->userID);
                $this->assertEquals(56000, $data->money);
                $this->assertEquals("sam", $data->name);
            }
        });



    }
}

############### test class ##################

class TestTable extends Table
{

    function getPropFactory()
    {
        return new TestTableProp("userID");
    }

    function getTableName()
    {
        return "testUserTable";
    }
}

class TestTableProp extends PropsFactory
{
    public int $userID = 0;
    public int $money = 10;
    public string $name = 'unset';
}