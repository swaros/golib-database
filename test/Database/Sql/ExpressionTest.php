<?php

namespace Database\Sql;

use golibdatabase\Database\Sql\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testExpression() {
        $expr = new Expression('test');
        $this->assertEquals("test", (string)$expr);
    }

    public function testExpressionConvert() {
        $expr = new Expression('test', true);
        $this->assertEquals("`test`", (string)$expr);
    }
}
