<?php

require_once("class/model/Values.php");
abstract class ImportElement { //elemento a importar
    public $index;
    public $warnings = [];
    public $errors = [];
    public $process = true;
    public $sql = "";
    public $entities = [];

    public function __construct($i, $data){
        $this->index = $i;
        $this->setEntities($data);
    }

    abstract function setEntities($data);

    public function setEntity_($name, $row){
        $this->entities[$name] = EntityValues::getInstanceRequire($name, $row, $name . "_");
      }

    public function addWarning($warning) { array_push($this->warnings, $warning); }
    public function addError($error) { array_push($this->errors, $error); }
}