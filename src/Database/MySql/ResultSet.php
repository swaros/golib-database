<?php
namespace golibdatabase\Database\MySql;

use mysqli_result;

/**
 * Description of ResultSet
 *
 * @author tziegler
 */
class ResultSet {

    private ?mysqli_result $res = NULL;
    private array $resultList = array();
    private ?string $error = NULL;
    private ?int $errorNr = NULL;
    private int $count = 0;

    public function applyRow(array $row){
        $this->resultList[] = $row;
        $this->count++;
    }

    public function applyRes(mysqli_result $res){
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
     * @return mysqli_result
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
