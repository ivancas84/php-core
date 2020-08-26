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
  public $db;
  
  public function __construct($i, $data){
      $this->index = $i;
      $this->logs = new Logs();
      $this->setEntities($data);
  }

  abstract function setEntities($data);
  /**
   * Cada entidad que se encuentra en $data debe definirse y asignarse
   * Cobra importancia el uso de prefijos que deben definirse para los encabezados
   * Puede utilizarse el metodo setEntity predefinido con el comportamiento básico de seteo o definirse uno propio
   * 
   * { 
   *   $this->setEntity($row, "persona", "per");
   *   $this->setEntity($row, "curso", "cur");
   *   $this->setAsignatura($row, "asi"); //ejemplo definido en la subclase
   * }
   */
  
  public function setEntity($data, $name, $prefix = ""){
    /**
     * Comportamiento por defecto para setear una entidad
     */
    $this->entities[$name] = $this->container->getValues($name);
    if(!$data) {
      $this->logs->addLog($name, "error", "Error al definir datos iniciales");                
      $this->process = false;
      return;
    }
    $this->entities[$name]->_setDefault();
    $this->entities[$name]->_fromArray($data, $prefix);
  }

  public function persist(){
    try {
      $this->db->multi_query_transaction($this->sql);
    } catch(Exception $exception){
      $this->logs->addLog("persist","error",$exception->getMessage());
    }
  }

  

  
  public function insert($name){
    if(Validation::is_empty($this->entities[$name]->id())) $this->entities[$name]->setId(uniqid()); 
    $persist = $this->container->getSqlo($name)->insert($this->entities[$name]->_toArray());
    $this->entities[$name]->setId($persist["id"]);
    $this->sql .=  $persist["sql"];
  }

  public function update($name, $existente){
    $this->entities[$name]->setId($existente->id());
    $compare =  $this->entities[$name]->_equalTo($existente);
    if($compare !== true) {
      $this->logs->addLog("persona","warning","El registro sera actualizado ({$compare})");
      $persist = $this->container->getSqlo($name)->update($this->entities[$name]->_toArray());
      $this->sql .= $persist["sql"];
    } else {
      $this->process = false;
      $this->logs->addLog("persona","info","Registros existente, no será actualizado");
    }
  }


  public function resetAndCheckEntities(){
    foreach($this->entities as $entityName => &$entity){
      if(!$entity->_reset()->_check()){
        foreach($entity->_getLogs()->getLogs() as $key => $errors) {
          foreach($errors as $error) {
            $this->logs->addLog($entityName, "warning", $key. " " . $error["status"] . " " . $error["data"]);
          }
        }
      }
    }
  }

  /*
  public function logsEntities(){
      $logs = [];
      foreach($this->entities as $entity) $logs = array_merge($logs, $entity->_getLogs()->getLogs());
      return $logs;
  }
  */
}