<?php

namespace golibdatabase\Database\MySql;

use golib\Types\Enum;

/**
 * Description of WhereMode
 *
 * @author tziegler
 */
class WhereMode extends Enum {

    public function __construct ( $default = WhereSet::USE_AND ) {
        parent::__construct( $default );
    }

    public function getPossibleValueArray () {
        return array(
            WhereSet::USE_AND,
            WhereSet::USE_OR);
    }

}
