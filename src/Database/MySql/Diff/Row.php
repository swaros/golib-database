<?php

namespace golibdatabase\Database\MySql\Diff;

use golib\Types\Props;

/**
 * Description of Row
 *
 * @author tziegler
 */
class Row {

    public $name = NULL;
    public $value = NULL;

    /**
     *
     * @var Props|null
     */
    public ?Props $originSet = NULL;

}
