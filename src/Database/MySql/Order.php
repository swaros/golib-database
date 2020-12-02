<?php

namespace golibdatabase\Database\MySql;

/**
 * Description of Order
 *
 * @author tziegler
 */
class Order {

    private array $fieldNames = array();
    private string $sort = 'ASC';

    public function setDesc () {
        $this->sort = 'DESC';
    }

    public function setAsc () {
        $this->sort = 'ASC';
    }

    public function addSortField ( $name ) {
        $this->fieldNames['`' . str_replace( '`', '', $name ) . '`'] = true;
    }

    public function getSortState () {
        if (count( $this->fieldNames ) > 0) {
            return ' ORDER BY ' . implode( ',', array_keys( $this->fieldNames ) ) . ' ' . $this->sort;
        }

        return '';
    }

}
