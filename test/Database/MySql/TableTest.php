<?php

namespace Database\MySql;

use Database\DatabaseTest;
use Exception;
use golib\Types\PropsFactory;
use golibdatabase\Database\MySql\Table;
use golibdatabase\Database\MySql\TableException;
use golibdatabase\Database\MySql\WhereMode;
use golibdatabase\Database\MySql\WhereSet;
use InvalidArgumentException;


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
    public function testFetchSize()
    {
        $table = new TestTable();

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT count(1) as CNT FROM `testUserTable`", [["CNT" => 5]]);

        $size = $table->fetchSize($mysql);
        $this->assertEquals(5, $size);
    }

    /**
     * @throws Exception
     */
    public function testFetchSizeWithWhere()
    {
        $table = new TestTable();
        $table->getWhere()->isGreater("userID", 0);

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult(
            $mysqli,
            "SELECT count(1) as CNT FROM `testUserTable` WHERE (`userID` > '0')",
            [["CNT" => 5]]
        );

        $size = $table->fetchSize($mysql);
        $this->assertEquals(5, $size);
    }

    /**
     * @throws Exception
     */
    public function testFetchDataWithWhere()
    {

        $table = new TestTable();
        $table->getWhere()->isEqual("userID", 2);

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult(
            $mysqli,
            "SELECT * FROM `testUserTable` WHERE (`userID` = '2')",
            $this->tableContentTwoUsers
        );

        $table->fetchData($mysql);
    }

    /**
     * @throws Exception
     */
    public function testFetchDataWithWhereAndLimit()
    {

        $table = new TestTable();
        $table->getWhere()->isEqual("userID", 2);
        $table->getLimit()->count = 10;


        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult(
            $mysqli,
            "SELECT * FROM `testUserTable` WHERE (`userID` = '2') LIMIT 10",
            $this->tableContentTwoUsers
        );

        $table->fetchData($mysql);
    }

    /**
     * @throws Exception
     */
    public function testFetchDataWithWhereAndLimitAndOrder()
    {

        $orUser = new WhereSet(WhereSet::USE_OR);
        $orUser->isEqual("userID", 2)
            ->isEqual("userID", 1);

        $table = new TestTable();
        $table->getWhere()
            ->applyWhere($orUser);

        $table->getLimit()->count = 20;
        $table->getOrder()
            ->addSortField("money")
            ->setAsc();


        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult(
            $mysqli,
            "SELECT * FROM `testUserTable` WHERE ((`userID` = '2' OR `userID` = '1'))  ORDER BY `money` ASC LIMIT 20",
            $this->tableContentTwoUsers
        );

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
        $execCnt = 0;
        $table->foreachCall(function (TestTableProp $data) use (&$execCnt) {
            $execCnt++;
            if ($execCnt === 1) {

                $this->assertEquals(1, $data->userID);
                $this->assertEquals(560, $data->money);
                $this->assertEquals("robert", $data->name);
            }
            if ($execCnt === 2) {
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
        $table->registerItem(new TestTableProp("userID", [
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

    /**
     * @throws Exception
     */
    public function testFindAndSetFieldNames()
    {
        $table = new TestTable();
        $table->setLoadFieldsAndIgnore();

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT testUserTable.userID,testUserTable.money,testUserTable.name FROM `testUserTable`", $this->tableContentTwoUsers);

        $this->assertNull($table->findFirstMatch("name", "carol"));

        $table->fetchData($mysql);

        $found = $table->findFirstMatch("name", "carol");
        $this->assertEquals(9968, $found->money);

        $notFound = $table->findFirstMatch("name", "mister-x");
        $this->assertNull($notFound);

        // disabled type check
        $foundByMoneyAsString = $table->findFirstMatch("money", "9968", false);
        $this->assertEquals(9968, $foundByMoneyAsString->money);
        $this->assertEquals("carol", $foundByMoneyAsString->name);

    }

    /**
     * @throws Exception
     */
    public function testFindFailByTypeAndIgnoreSet()
    {
        $table = new TestTable();
        $table->setLoadFieldsAndIgnore(['money']);

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT testUserTable.userID,testUserTable.name FROM `testUserTable`", $this->tableContentTwoUsers);
        $table->fetchData($mysql);
        $this->expectWarning();
        $table->findFirstMatch("money", "9968");


    }

    /**
     * @throws Exception
     */
    public function testIteration()
    {
        $table = new TestTableIteration();
        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `testUserTable`", $this->tableContentTwoUsers);
        $table->fetchData($mysql);
        $table->iterate("myIteration");

        $this->assertEquals("carol", $table->propsStored[2]->name);
        $this->assertEquals("robert", $table->propsStored[1]->name);
    }

    /**
     * @throws Exception
     */
    public function testIterationFail()
    {
        $table = new TestTable();
        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `testUserTable`", $this->tableContentTwoUsers);
        $table->fetchData($mysql);
        $this->expectException(InvalidArgumentException::class);
        $table->iterate("myIteration");

    }

    /**
     * @throws Exception
     */
    public function testInvalidPropSetup()
    {
        $table = new TestTableFailure();
        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `someTable`", $this->tableContentTwoUsers);
        $this->expectException(TableException::class);
        $table->fetchData($mysql);
    }
}

############### test class ##################

class TestTable extends Table
{

    function getPropFactory(): TestTableProp
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

class TestTableIteration extends TestTable
{
    /**
     * @var TestTableProp[]
     */
    public array $propsStored = array();

    public function myIteration(TestTableProp $prop)
    {
        $this->propsStored[$prop->userID] = $prop;
    }
}

class TestTableFailure extends Table
{

    function getPropFactory()
    {
        return "";
    }

    function getTableName()
    {
        return "someTable";
    }
}