<?php


abstract class ImportElement { //2
  /**
   * Elemento a importar
   */

  public $index;
  /**
   * @property $index: Identificacion del elemento.
   *  
   * Habitualmente es un numero incremental pero cuando los datos de entrada 
   * indentifican mas de un juego de entidades se utiliza un string.
   */
  public $logs;
  public $process = true;
  public $sql = "";
  public $entities = [];
  public $db;
  public $container;
  public $updateMode = true; //actualizar existentes
  public $updateNull = true; //actualizar valores nulos

  public function id(){
    $fields = [];
    foreach($this->entities as $entity) {
      if(!Validation::is_empty($entity->_get("identifier"))) array_push($fields, $entity->_get("identifier"));
      else array_push($fields, $entity->_toString()); 
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
   *   $this->entities["alumno"]->_set("ingreso", preg_replace("/[^0-9]/", "", $this->entities["alumno"]->_get("ingreso"))); // ejemplo de cambio de valor particular
   * }
   */

  public function setEntity($data, $name, $prefix = "", $id = ""){
    /**
     * Comportamiento por defecto para setear una entidad
     */
    if(empty($id)) $id = $name;
    $this->entities[$id] = $this->container->getValue($name, $prefix);
    if(!$data) {
      $this->logs->addLog($id, "error", "Error al definir datos iniciales");                
      $this->process = false;
      return;
    }
    $this->entities[$id]->_fromArray($data, "set");
  }

  public function persist(){
    if(empty($this->sql)) return;
    try {
      $this->container->getDb()->multi_query_transaction($this->sql);
    } catch(Exception $exception){
      $this->logs->addLog("persist","error",$exception->getMessage());
    }
  }

  public function insert($entityName,$id = ""){
    if(empty($id)) $id = $entityName;
    $this->logs->addLog($id,"info","Se realizara una insercion");
    if(Validation::is_empty($this->entities[$id]->_get("id"))) $this->entities[$id]->_set("id",uniqid());
    $this->entities[$id]->_call("setDefault");
    $this->sql .= $this->container->getSqlo($entityName)->insert($this->entities[$id]->_toArray("sql"));
    return true;
  }

  public function update($entityName, $existente, $id = "", $updateMode = true){
    if(empty($id)) $id = $entityName;
    if($updateMode) {
      $this->logs->addLog($id,"info","Registro existente, se actualizara campos");
      $this->sql .= $this->container->getSqlo($entityName)->update($this->entities[$id]->_toArray("sql"));
    } else {
      $this->process = false;
      $this->logs->addLog($id,"error","El registro debe ser actualizado, comparar");
      return false;
    }
    return true;
  }

  public function compare(string $id, $existent, $updateNull = false){
    $a = $this->entities[$id]->_toArray("sql");
    $b = $existent->_toArray("sql");
    $compare = [];
    foreach($a as $ka => $va) {
      if((!$updateNull && (is_null($va) || $va == "null")) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) array_push($compare, $ka);
    }
    return $this->compareResult($compare, $id, $a, $b);
  }

  public function compareResult($compare, $id, $a, $b){
    if(empty($compare)) {
      $this->logs->addLog($id,"info","Registro existente, no será actualizado");
    } else{

      $cc = [];
      foreach($compare as $c){
        array_push($cc, $c. " (" . $a[$c] . " != " . $b[$c] . ")");
      }

      $this->logs->addLog($id,"info","Registro existente, valores diferentes " . implode(", ", $cc));
    }
    return $compare;
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

  public function getIdentifier($entityName, $fieldName = "identifier"){
    /**
     * Obtiene identificador
     * Si el identificador es vacio, asigna un error al elemento y retorna false
     */
    $identifier = $this->entities[$entityName]->_get($fieldName);

    if(Validation::is_empty($identifier)){
      $this->process = false;                
      $this->logs->addLog($entityName, "error", "El identificador de $entityName no se encuentra definido");
      return false;
    }
    return $identifier;
  }

  /*
  public function logsEntities(){
      $logs = [];
      foreach($this->entities as $entity) $logs = array_merge($logs, $entity->_getLogs()->getLogs());
      return $logs;
  }
  */
}