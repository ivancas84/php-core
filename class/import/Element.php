<?php


abstract class ImportElement {
  /**
   * Elemento a importar
   */

  public $entityName;
  public $index;
  public $logs;
  public $process = true;
  public $sql = "";
  public $entities = [];
  public $db;
  public $container;
  public $updateMode = true; //actualizar existentes
  public $updateNull = false; //actualizar valores nulos

  public function id(){
    $fields = [];
    foreach($this->entities as $entity) {
      array_push($fields, $entity->_toString()); 
    }
    return implode(",", $fields);
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
    $this->entities[$name] = $this->container->getValue($name, $prefix);
    if(!$data) {
      $this->logs->addLog($name, "error", "Error al definir datos iniciales");                
      $this->process = false;
      return;
    }
    $this->entities[$name]->_fromArray($data, "set");
  }

  public function persist(){
    try {
      $this->container->getDb()->multi_query_transaction($this->sql);
    } catch(Exception $exception){
      $this->logs->addLog("persist","error",$exception->getMessage());
    }
  }

  public function insert($name){
    if(Validation::is_empty($this->entities[$name]->_get("id"))) $this->entities[$name]->_set("id",uniqid());
    $this->entities[$name]->_call("setDefault");
    $sql = $this->container->getSqlo($name)->insert($this->entities[$name]->_toArray("sql"));
    $this->entities[$name]->_set("id", $this->entities[$name]->_get("id"));
    $this->sql .= $sql;
  }

  public function update($name, $existente){
    $this->entities[$name]->_set("id",$existente->_get("id"));
    $compare =  $this->compare($this->entities[$name], $existente);
    if($compare !== true) {
      if($this->updateMode == "update") {
        $this->logs->addLog("persona","info","Registro existente, se actualizara campos {$compare}");
        $sql = $this->container->getSqlo($name)->update($this->entities[$name]->_toArray("sql"));
        $this->sql .= $sql;
      } else {
        $this->logs->addLog("persona","error","El registro debe ser actualizado, comparar {$compare}");
      }
    } else {
      $this->process = false;
      $this->logs->addLog($name,"info","Registros existente, no será actualizado");
    }
  }

  public function compare($new, $existent){
    $a = $new->_toArray("sql");
    $b = $existent->_toArray("sql");    
      
    $compare = [];
    foreach($a as $ka => $va) {
      if((!$this->updateNull && is_null($va)) || !key_exists($ka, $b)) break;
      if($b[$ka] !== $va) array_push($compare, $ka);
    }
    return (empty($compare)) ? true : implode(", ", $compare);
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