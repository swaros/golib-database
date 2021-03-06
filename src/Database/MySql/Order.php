<?php

namespace golibdatabase\Database\MySql;

/**
 * Description of Order
 *
 * @author tziegler
 */
class Order {

    private $fieldnames = array();
    private $sort = 'ASC';

    public function setDesc () {
        $this->sort = 'DESC';
    }

    public function setAsc () {
        $this->sort = 'ASC';
    }

    public function addSortField ( $name ) {
        $this->fieldnames['`' . str_replace( '`', '', $name ) . '`'] = true;
    }

    public function getSortState () {
        if (count( $this->fieldnames ) > 0) {
            $sort = ' ORDER BY ' . implode( ',', array_keys( $this->fieldnames ) ) . ' ' . $this->sort;
            return $sort;
        }

        return '';
    }

}
