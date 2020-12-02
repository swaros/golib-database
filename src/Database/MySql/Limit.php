<?php
namespace golibdatabase\Database\MySql;

/**
 * Description of Limit
 *
 * @author tziegler
 */
class Limit {
    public string|int|null $start = NULL;
    public string|int|null $count = NULL;

    /**
     * is the combination of
     * parameters valid
     * @return boolean
     */
    public function isUsable(){
        return  ($this->count != null);
    }

    /**
     * Builds MySQL Limit Expression
     * @return string
     */
    public function getLimitStr(){
        if ($this->start === null && $this->count !== null){
            return "LIMIT {$this->count}";
        } elseif ($this->start !== null && $this->count !== null) {
            return "LIMIT {$this->start},{$this->count}";
        } else {
            return "";
        }
    }
}
