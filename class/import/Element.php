<?php

require_once("class/tools/Logs.php");
require_once("class/model/Values.php");

abstract class ImportElement {
    /**
     * Elemento a importar
     */
    
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

    abstract function setEntities($data);
    
    public function setEntity($data, $name, $prefix = ""){
      /**
       * Comportamiento por defecto para setear una entidad
       */
      $this->entities[$name] = EntityValues::getInstanceRequire($name);
      if(!$data) {
        $this->logs->addLog("define", "error", "Error al definir datos iniciales");                
        $this->process = false;
        return;
      }
      $this->entities[$name]->_setDefault();
      $this->entities[$name]->_fromArray($data, $prefix);
    }

    public function logsEntities(){
        $logs = [];
        foreach($this->entities as $entity) $logs = array_merge($logs, $entity->_getLogs()->getLogs());
        return $logs;
    }

}