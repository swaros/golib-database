<?php

namespace Database\MySql;

use golibdatabase\Database\MySql\WhereSet;
use golibdatabase\Database\Sql\Expression;
use PHPUnit\Framework\TestCase;

class WhereSetTest extends TestCase
{

    public function testExpression()
    {
        $e = new Expression("check = 1");
        $w = new WhereSet();
        $w->expression($e);
        $w->isEqual("a","b");

        $this->assertEquals(
            "(`a` = 'b' AND check = 1)",
            $w->getWhereCondition()
        );

    }


    public function testIsGreater()
    {
        $w = new WhereSet();
        $w->isGreater("a","b");

        $this->assertEquals(
            "(`a` > 'b')",
            $w->getWhereCondition()
        );
    }

    public function testIsLower()
    {
        $w = new WhereSet();
        $w->isLower("a","b");

        $this->assertEquals(
            "(`a` < 'b')",
            $w->getWhereCondition()
        );
    }

    public function testApplyWhere()
    {
        $w = new WhereSet();
        $w->isGreater("a","b");
        $w2 = new WhereSet(WhereSet::USE_OR);
        $w2->isIn("D", 'A,B,C,D,E');
        $w2->isNotEqual("D", "X");
        $w->applyWhere($w2);

        $this->assertEquals(
            "(`a` > 'b' AND (`D` != 'X' OR `D` in ('A','B','C','D','E')))",
            $w->getWhereCondition()
        );
    }


    public function testIsNotIn()
    {
        $w = new WhereSet();
        $w->isGreater("a",800);
        $w2 = new WhereSet(WhereSet::USE_OR);
        $w2->isNotIn("D", 'A,B,C,D,E');
        $w2->isEqual("H", null);
        $w->applyWhere($w2);

        $this->assertEquals(
            "(`a` > '800' AND (`H` is null  OR `D` not in ('A','B','C','D','E')))",
            $w->getWhereCondition()
        );
    }

}
