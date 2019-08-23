<?php

abstract class ImportElement { //elemento a importar
    public $index;
    public $warnings = [];
    public $errors = [];
    public $process = true;
    public $sql = "";

    public function __construct($i, $data){
        $this->$index = $i;
        $this->setData($data);
    }

    abstract function setData($data);

    public function addWarning($warning) { array_push($this->warnings, $warning); }
    public function addError($error) { array_push($this->errors, $error); }
}