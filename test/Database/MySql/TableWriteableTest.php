<?php

namespace Database\MySql;

use Database\DatabaseTest;
use Exception;
use golib\Types\MapConst;
use golib\Types\PropsFactory;
use golibdatabase\Database\Model\LoggingEntryPoint;
use golibdatabase\Database\MySql\TableWriteable;

require_once __DIR__ . "/../DatabaseTest.php";


class TableWriteableTest extends DatabaseTest
{
    // database entries
    private array $dataBase = [
        [
            'primaryKeyId' => 1,
            'userID' => 100,
            'entryName' => 'item-a',
            'entryAmount' => 100,
        ],

        [
            'primaryKeyId' => 2,
            'userID' => 100,
            'entryName' => 'item-b',
            'entryAmount' => 177,
        ],

        [
            'primaryKeyId' => 3,
            'userID' => 100,
            'entryName' => 'item-c',
            'entryAmount' => 99987,
        ],
        [
            'primaryKeyId' => 4,
            'userID' => 201,
            'entryName' => 'item-a',
            'entryAmount' => 21,
        ],

        [
            'primaryKeyId' => 5,
            'userID' => 201,
            'entryName' => 'item-b',
            'entryAmount' => 0,
        ],

        [
            'primaryKeyId' => 6,
            'userID' => 600,
            'entryName' => 'item-a',
            'entryAmount' => 65,
        ],
    ];

    /**
     * @throws Exception
     */
    public function testBasic()
    {
        $table = new TestTableWritable();
        /**
        $table->setErrorHandlers(function ($message, $logLevel){
            echo "LOG:" . $message . PHP_EOL;
        },LoggingEntryPoint::DEBUG);
        */

        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `user_items`", $this->dataBase);

        $table->fetchData($mysql);

        $newProp = $table->getPropFactory();
        $newProp->userID = 666;
        $newProp->entryName = 'new-item';
        $newProp->entryAmount = 200;
        $table->insert($newProp);
        $prop = $table->findFirstMatch("userID", 666);
        if ($prop instanceof TestTableWritableProps){
            $this->assertEquals("new-item", $prop->entryName);
        }

        $this->createUpdateResult(
            $mysqli,
            "INSERT INTO `user_items` (`user_items`.userID,`user_items`.entryName,`user_items`.entryAmount) VALUES ('666','new-item','200')",
            1
        );
        $table->save($mysql);

        $this->createUpdateResult(
            $mysqli,
            "UPDATE user_items SET user_items.entryAmount = '10' WHERE (`primaryKeyId` = '2' AND `userID` = '100' AND `entryName` = 'item-b' AND `entryAmount` = '177')",
            1
        );

        $updateProp = $table->findFirstMatch("primaryKeyId", 2);
        if ($updateProp instanceof TestTableWritableProps) {
            // update prop
            $updateProp->entryAmount = 10;
            $table->save($mysql);
        } else {
            $this->fail("unexpected return value");
        }

    }

    /**
     * @throws Exception
     */
    public function testBasicInsertOnDuplicate()
    {
        $table = new TestTableWritable();
        $table->setDuplicateKeyHandling(true);
        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `user_items`", $this->dataBase);
        $table->fetchData($mysql);

        $newProp = $table->getPropFactory();
        $newProp->userID = 666;
        $newProp->entryName = 'new-item';
        $newProp->entryAmount = 200;
        $table->insert($newProp);
        $prop = $table->findFirstMatch("userID", 666);
        if ($prop instanceof TestTableWritableProps){
            $this->assertEquals("new-item", $prop->entryName);
        }

        $this->createUpdateResult(
            $mysqli,
            "INSERT INTO `user_items` (`user_items`.userID,`user_items`.entryName,`user_items`.entryAmount) VALUES ('666','new-item','200') ON DUPLICATE KEY UPDATE `user_items`.userID = VALUES(`user_items`.userID),`user_items`.entryName = VALUES(`user_items`.entryName),`user_items`.entryAmount = VALUES(`user_items`.entryAmount)",
            1
        );
        $table->save($mysql);

        $this->createUpdateResult(
            $mysqli,
            "UPDATE user_items SET user_items.entryAmount = '10' WHERE (`primaryKeyId` = '2' AND `userID` = '100' AND `entryName` = 'item-b' AND `entryAmount` = '177')",
            1
        );

        $updateProp = $table->findFirstMatch("primaryKeyId", 2);
        if ($updateProp instanceof TestTableWritableProps) {
            // update prop
            $updateProp->entryAmount = 10;
            $table->save($mysql);
        } else {
            $this->fail("unexpected return value");
        }

    }

    /**
     * @throws Exception
     */
    public function testMultipleInserts()
    {
        $table = new TestTableWritable();
        $table->setDuplicateKeyHandling(true);
        $mysqli = $this->getMysqliMock();
        $mysql = $this->getMysqlWithMocks($mysqli);
        $this->createFetchArrayResult($mysqli, "SELECT * FROM `user_items`", $this->dataBase);
        $table->fetchData($mysql);

        $newProp = $table->getPropFactory();
        $newProp->userID = 666;
        $newProp->entryName = 'new-item';
        $newProp->entryAmount = 200;
        $table->insert($newProp);

        $newProp = $table->getPropFactory();
        $newProp->userID = 55;
        $newProp->entryName = 'lamp';
        $newProp->entryAmount = 77;
        $table->insert($newProp);


        $prop = $table->findFirstMatch("userID", 666);
        if ($prop instanceof TestTableWritableProps){
            $this->assertEquals("new-item", $prop->entryName);
        }

        $this->createUpdateResult(
            $mysqli,
            "INSERT INTO `user_items` (`user_items`.userID,`user_items`.entryName,`user_items`.entryAmount) VALUES ('666','new-item','200'),('55','lamp','77') ON DUPLICATE KEY UPDATE `user_items`.userID = VALUES(`user_items`.userID),`user_items`.entryName = VALUES(`user_items`.entryName),`user_items`.entryAmount = VALUES(`user_items`.entryAmount)",
            1
        );
        $table->save($mysql);


    }
}


################ test class #########

class TestTableWritable extends TableWriteable
{

    function getPropFactory()
    {
        return new TestTableWritableProps('primaryKeyId');
    }

    function getTableName()
    {
        return "user_items";
    }
}

class TestTableWritableProps extends PropsFactory
{
    public int|string $primaryKeyId = MapConst::AUTOINC;
    public int $userID = 0;
    public string $entryName = '';
    public int $entryAmount = 0;
}