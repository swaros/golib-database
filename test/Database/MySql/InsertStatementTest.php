<?php

namespace Database\MySql;

use Exception;
use golib\Types\MapConst;
use golib\Types\Props;
use golibdatabase\Database\MySql\InsertStatement;
use PHPUnit\Framework\TestCase;

class InsertStatementTest extends TestCase
{
    public function testCreation()
    {
        $statement = new InsertStatement('test');
        $statement->addField("indexField", false);
        $statement->addField("count", true);

        $statement->addValues("count", 5)
            ->addValues("indexField", 1)
            ->rowDone();

        $query = $statement->createInsertStatement();
        $expectedResult = "INSERT INTO `test` (`test`.indexField,`test`.count) VALUES ('1','5')".
            " ON DUPLICATE KEY UPDATE `test`.indexField = ".
            "VALUES(`test`.indexField),`test`.count = `test`.count + VALUES(`test`.count)";
        $this->assertEquals($expectedResult, $query);
    }

    /**
     * @throws Exception
     */
    public function testWithProps() {
        $statement = new InsertStatement('test');
        $userA = new TestStmtProp([
            "userId" => 100,
            "name" => "tester1",
            "money" => 1000,
        ]);
        $statement->addFieldsByProps($userA, [],false)
            ->addValues("name", "georg")
            ->addValues("money", 5)
            ->addValues("userId", 88888)
            ->rowDone();

        $query = $statement->createInsertStatement();
        $expectedResult = "INSERT INTO `test` (`test`.name,`test`.money,`test`.userId)".
            " VALUES ('georg','5','88888') ".
            "ON DUPLICATE KEY UPDATE `test`.name = ".
            "VALUES(`test`.name),`test`.money = ".
            "VALUES(`test`.money),`test`.userId = VALUES(`test`.userId)";


        $this->assertEquals($expectedResult, $query);
    }
}

############## test class ###############

class TestStmtProp extends Props {
    public int|string $userID = MapConst::AUTOINC;
    public string $name = '';
    public int $money = 0;
}