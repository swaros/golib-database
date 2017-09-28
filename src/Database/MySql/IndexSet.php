<?php

namespace golibdatabase\Database\MySql;

use golib\Types\Props;

/**
 * Description of IndexSet
 *
 * @author tziegler
 */
class IndexSet extends Props {

    public $Table = NULL;
    public $Non_unique = '';
    public $Key_name = '';
    public $Seq_in_index = '';
    public $Column_name = '';
    public $Collation = '';
    public $Sub_part = '';
    public $Packed = '';
    public $Null = '';
    public $Index_type = '';
    public $Comment = '';

}
