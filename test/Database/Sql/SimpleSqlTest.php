<?php

namespace Database\Sql;

use golibdatabase\Database\Sql\SimpleSql;
use PHPUnit\Framework\TestCase;

class SimpleSqlTest extends TestCase
{

    public function testSqlString()
    {
        $sql = new SimpleSql();
        $result = $sql->sqlString("replace ? and ?",["A", "B"]);
        $this->assertEquals("replace 'A' and 'B'", $result);

        $result = $sql->sqlString("replace ? and ? and also ?","A", "B", "C");
        $this->assertEquals("replace 'A' and 'B' and also 'C'", $result);

        $result = $sql->sqlString("replace ? and ? and also ?");
        $this->assertEquals("replace ? and ? and also ?", $result);

    }
}
