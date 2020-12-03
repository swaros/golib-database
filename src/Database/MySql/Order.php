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

    public function setDesc (): self {
        $this->sort = 'DESC';
        return $this;
    }

    public function setAsc (): self {
        $this->sort = 'ASC';
        return $this;
    }

    public function addSortField ( $name ): self {
        $this->fieldNames['`' . str_replace( '`', '', $name ) . '`'] = true;
        return $this;
    }

    public function getSortState (): string {
        if (count( $this->fieldNames ) > 0) {
            return ' ORDER BY ' . implode( ',', array_keys( $this->fieldNames ) ) . ' ' . $this->sort;
        }

        return '';
    }

}
