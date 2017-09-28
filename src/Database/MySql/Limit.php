<?php
namespace golibdatabase\Database\MySql;

/**
 * Description of Limit
 *
 * @author tziegler
 */
class Limit {
    public $start = NULL;
    public $count = NULL;

    /**
     * is the combination of
     * parameters valid
     * @return boolean
     */
    public function isUsable(){
        return  ($this->count != NULL);
    }

    /**
     * Builds MySQL Limit Expression
     * @return string
     */
    public function getLimitStr(){
        if ($this->start === NULL){
            return "LIMIT {$this->count}";
        } elseif ($this->start !== NULL && $this->count !== NULL) {
            return "LIMIT {$this->start},{$this->count}";
        } else {
            return "";
        }
    }
}
