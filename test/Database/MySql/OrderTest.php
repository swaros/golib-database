<?php

namespace Database\MySql;

use golibdatabase\Database\MySql\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testOrder() {
        $order = new Order();

        $state = $order->getSortState();
        $this->assertEquals("", $state);

        $order->addSortField("column1");
        $order->setAsc();

        $state = $order->getSortState();
        $this->assertEquals(" ORDER BY `column1` ASC", $state);

        $order->addSortField("column2");

        $state = $order->getSortState();
        $this->assertEquals(" ORDER BY `column1`,`column2` ASC", $state);

        $order->setDesc();
        $state = $order->getSortState();
        $this->assertEquals(" ORDER BY `column1`,`column2` DESC", $state);
    }
}
