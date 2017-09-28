<?php
namespace golibdatabase\Database\MySql;

/**
 * Description of ResultSet
 *
 * @author tziegler
 */
class ResultSet {
    /**
     *
     * @var mysqli_result
     */
    private $res = NULL;
    private $resultList = array();
    private $error = NULL;
    private $errorNr = NULL;
    private $count = 0;

    public function applyRow(array $row){
        $this->resultList[] = $row;
        $this->count++;
    }

    public function applyRes(\mysqli_result $res){
        $this->res = $res;
    }

    public function getResult(){
        return $this->resultList;
    }

    public function getError(){
        return $this->error;
    }

    public function getErrorNr(){
        return $this->errorNr;
    }

    public function setError($error){
        $this->error = $error;
    }

    public function setErrorNr($error){
        $this->errorNr = $error;
    }

    /**
     *
     * @return \mysqli_result
     */
    public function getRes(){
        return $this->res;
    }

    public function count(){
        return $this->count;
    }

    public function clear(){
        $this->resultList = array();
        $this->count = 0;
    }

}
