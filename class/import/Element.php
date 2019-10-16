<?php

require_once("class/Logs.php");

abstract class ImportElement {
    public $index;
    public $logs;
    public $process = true;
    public $sql = "";
    public $entities = [];

    public function __construct($i, $data){
        $this->index = $i;
        $this->logs = new Logs();
        $this->setEntities($data);
    }

    public function logs() { return $this->logs(); }
    abstract function setEntities($data);
    abstract function logsEntities();

}