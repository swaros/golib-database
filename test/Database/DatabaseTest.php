<?php


namespace Database;

require_once "Mocks/MysqliMock.php";

use Database\Mocks\MysqliMock;
use golibdatabase\Database\MySql;
use golibdatabase\Database\MySql\Connect;
use golibdatabase\Database\MySql\ConnectInfo;
use mysqli_result;
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->checkMysqli();
    }

    /**
     * creates a mocked MySql class with
     * expectations that the connection is
     * called at least ones.
     * @param MysqliMock $fakeMock
     * @return MySql
     */
    protected function getMysqlWithMocks(MysqliMock $fakeMock): MySql
    {

        $conMock = $this->getMockBuilder(Connect::class)
            ->getMock();
        $conMock->expects($this->atLeastOnce())
            ->method("getConnection")
            ->willReturn($fakeMock);

        $conMock->expects($this->atLeastOnce())
            ->method("isConnected")
            ->willReturn(true);

        $fakeMock->setCallbackForUndefined(function ($query){
            $this->fail("undefined query expectations for [{$query}]. you can use createFetchArrayResult to defined expectations");
        });

        $con = new ConnectInfo("user", "pw", "somewhere", "check");
        return new MySql($con, $conMock);
    }

    protected function createFetchArrayResult(MysqliMock $fakeMysqli, string $query, array $dataReturned): MysqliMock
    {
        $fakeResult = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();

        $callCount = 0;
        $fakeResult->expects($this->atLeastOnce())
            ->method('fetch_array')
            ->will($this->returnCallback(function ($whatEver) use ($dataReturned, &$callCount) {
                if ($callCount < count($dataReturned)) {
                    $data = $dataReturned[$callCount];
                    $callCount++;
                    return $data;
                }
                return false;
            }));
        $fakeMysqli->affected_rows = count($dataReturned);
        $fakeMysqli->setQueryResult($fakeResult, $query);
        $fakeMysqli->setCallbackForUndefined(function ($query){
            $this->fail("unexpected query [{$query}]");
        });
        return $fakeMysqli;
    }

    protected function createUpdateResult(MysqliMock $fakeMysqli, string $query, int $affectedRows): MysqliMock
    {
        $fakeResult = $this->getMockBuilder(mysqli_result::class)
            ->disableOriginalConstructor()
            ->getMock();


        $fakeMysqli->affected_rows = $affectedRows;
        $fakeMysqli->setQueryResult($fakeResult, $query);
        $fakeMysqli->setCallbackForUndefined(function ($query){
            $this->fail("unexpected query " . $query);
        });
        return $fakeMysqli;
    }


    protected function checkMysqli()
    {
        if (!extension_loaded('mysqli')) {
            $this->markTestSkipped(
                'The MySQLi extension is not available.'
            );
        }
    }

    protected function getMysqliMock(): MysqliMock
    {
        return new MysqliMock();
    }

    /**
     * dummy test so php unit is not complaining
     */
    public function testMock()
    {
        $this->assertInstanceOf(MysqliMock::class, $this->getMysqliMock());
    }
}