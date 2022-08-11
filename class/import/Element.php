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
  public $notCompare = []; //fields a ignorar en la comparacion

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
  }


  public function compareUpdate($entityName, $id, $name){
    /**
     * Realiza la comparacion y actualiza
     * 
     * Este metodo se define de forma independiente para facilitar su reimplementacion
     * @param $id Valor del identificador
     * @param $name Nombre alternativo de la entityName que es utilizado para identificar la entidad
     */
    $existente = $this->container->getValue($entityName);
    $existente->_fromArray($this->import->dbs[$name][$id], "set");
    $this->entities[$name]->_set("id",$existente->_get("id"));
    $compare = $this->compare($name, $existente);  
    return $this->update($compare, $entityName, $existente, $name);
  }

  public function update($compare, $entityName, $existente, $name, $updateNullExistent = false){
    /**
     * Analiza el resultado de la comparacion y decide la accion a realizar
     * 
     * Se espera que este metodo sea predefinido en funcion de las necesidades
     * de cada entidad
     * 
     * Este metodo se define de forma independiente para facilitar su reimplementacion
     * Es comun tomar decisiones dependiendo del resultado de la comparacoin
     **/
    if(empty($compare)) {
      $this->logs->addLog($name,"info","Registro existente, campos identicos, no se realizara actualizacion");
      return;
    }
    
    if($updateNullExistent) {
      $compareAux = [];
      foreach($compare as $key){
        if(!is_null($existente->_get($key))) array_push($compareAux, $key);
      }
    } else {
      $compareAux = $compare;
    }

    if(!empty($compareAux)) throw new Exception("El registro debe ser actualizado, comparar");
    $this->logs->addLog($name,"info","Registro existente, se actualizara campos");
    $this->sql .= $this->container->getSqlo($entityName)->update($this->entities[$name]->_toArray("sql"));
   


    return true;
    
  }

  public function compare(string $id, $existent, $updateNull = false){
    /**
     * Comparacion para determinar si se actualiza o no
     * 
     * @return 
     *   false si algo falla (es mas que nada si se reimplementa)
     *   array vacio si los campos son iguales
     *   array de strings con los campos diferentes
     */
    $a = $this->entities[$id]->_toArray("sql");
    $b = $existent->_toArray("sql");
    $compare = [];
    foreach($a as $ka => $va) {
      if((!$updateNull && (is_null($va) || $va == "null")) || !key_exists($ka, $b)) continue;
      if($b[$ka] !== $va) array_push($compare, $ka);
    }
    $this->logCompare($compare, $id, $a, $b);
    return $compare;
  }

  public function logCompare($compare, $id, $a, $b){
    if(empty($compare)) {
      $this->logs->addLog($id,"info","Registro existente, no será actualizado");
    } else{

      $cc = [];
      foreach($compare as $c){
        array_push($cc, $c. " (" . $a[$c] . " != " . $b[$c] . ")");
      }

      $this->logs->addLog($id,"info","Registro existente, valores diferentes " . implode(", ", $cc));
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