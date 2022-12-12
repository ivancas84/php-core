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
  public $entities = []; //instancias de Values para cada entidad
  public $container;
  public $import = null; //referencia a la clase de importacion para acceder a atributos y datos adicionales

  public function id(){
    $fields = [];
    foreach($this->entities as $entity) {
      if(!Validation::is_empty($entity->_get("identifier"))) array_push($fields, $entity->_get("identifier"));
      else array_push($fields, $entity->_toString()); 
    }
    return implode(",", $fields);
  }

  /**
   * Cada entidad que se encuentra en $data debe definirse y asignarse
   * Cobra importancia el uso de prefijos que deben definirse para los encabezados
   * Puede utilizarse el metodo setEntity predefinido con el comportamiento básico de seteo o definirse uno propio
   * 
   * @example { 
   *   $this->setEntity($row, "persona", "per");
   *   $this->setEntity($row, "curso", "cur");
   *   $this->setAsignatura($row, "asi"); //ejemplo definido en la subclase
   *   $this->entities["alumno"]->_set("ingreso", preg_replace("/[^0-9]/", "", $this->entities["alumno"]->_get("ingreso"))); // ejemplo de cambio de valor particular
   * }
   */

  abstract function setEntities($data);
  
  /**
   * Comportamiento por defecto para setear una entidad
   */
  public function setEntity($data, $name, $prefix = ""){
    $entityName = $this->import->getEntityName($name);
    $this->entities[$name] = $this->container->value($entityName, $prefix);
    if(!$data) throw new Exception("Error al definir datos iniciales");
    $this->entities[$name]->_fromArray($data, "set");

  }

  
  /**
   * Persistencia del SQL
   */
  public function persist(){
    if(empty($this->sql)) return;
    try {
      $this->container->db()->multi_query_transaction($this->sql);
    } catch(Exception $exception){
      $this->logs->addLog("persist","error",$exception->getMessage());
    }
  }


  public function insert($name){
    $this->logs->addLog($name,"info","Se realizara una insercion");
    if(Validation::is_empty($this->entities[$name]->_get("id"))) $this->entities[$name]->_set("id",uniqid());
    $this->entities[$name]->_call("setDefault");
    $entityName = $this->import->getEntityName($name);
    $this->sql .= $this->container->persist($entityName)->insert($this->entities[$name]->_toArray("sql"));
    return $this->entities[$name]->_get("id");
  }

  /**
   * Realiza la comparacion y actualiza
   * 
   * Este metodo se define de forma independiente para facilitar su reim-
   * plementacion
   * 
   * @param $includeNull false No se tienen en cuenta en la comparacion los 
   * valores null para la entidad actual
   * @param $includeNull true Si se tienen en cuenta
   * @return false Si existio un error al realizar la comparacion
   * @return Array con los valores diferentes Si la comparacion se realizo correctamente
   */
  public function compare($name, $includeNull = false, $ignoreFields = []){
    if(!$existent = $this->exists($name)) return false;
    $a = $this->entities[$name]->_toArray("sql");
    $b = $existent->_toArray("sql");
    $compare = [];
    foreach($a as $ka => $va) {
      if(
        in_array($ka, $ignoreFields) ||
        (
          (
            !$includeNull && 
            (
              is_null($va) || $va == "null"
            )
          ) 
          || !key_exists($ka, $b)
        )
      ) continue;
      if($b[$ka] !== $va) array_push($compare, $ka);
    }
    return $compare;
  }

  /**
   * Filtro de valores no nulos para una comparacion
   */
  public function filterNotNullExistentComparition($name, $compare){
    if(!$existent = $this->exists($name)) return false;
    $compareAux = [];
    foreach($compare as $key){

      if(!is_null($existent->_get($key))) array_push($compareAux, $key);
    }
    return $compareAux;
  }

  /**
   * Deben realizarse los chequeos previos para confirmar la existencia
   */
  public function exists($name){
    $identifier = $this->getIdentifier($name);
    if(!$identifier = $this->getIdentifier($name)) return false;
    if(!array_key_exists($identifier, $this->import->dbs[$name])) return false;
    $entityName = $this->import->getEntityName($name);
    $existente = $this->container->value($entityName);
    $existente->_fromArray($this->import->dbs[$name][$identifier], "set");
    $this->entities[$name]->_set("id",$existente->_get("id"));
    return $existente;
  }

  public function update($name){
    $entityName = $this->import->getEntityName($name);
    $this->sql .= $this->container->persist($entityName)->update($this->entities[$name]->_toArray("sql"));
    $this->logs->addLog($name,"info","Registro existente, se actualizara campos");
    return $this->entities[$name]->_get("id");
  }

  /**
   * Log de actualizacion prohibida debido a valores diferentes
   */
  public function updateForbidden($name){
    $this->logs->addLog($name, "Error", "");
    $this->process = false;
  }

  /**
   * @return false si ocurrio un error al identificar (consultar logs)
   * @return $identifier si se identifico correctamente
   */
  public function getIdentifier($name){
    $identifier = $this->entities[$name]->_get("identifier");
    if(Validation::is_empty($identifier)) throw new Exception("El identificador de $name no se encuentra definido");
    return $identifier;
  }

  /**
   * Identificar y verificar existencia
   * 
   * @return true si la identificacion fue correcta
   * @return false si la identificacion no fue correcta (consultar logs)
   */
  public function identifyCheck($name){
    $identifier = $this->getIdentifier($name);
    if(empty($identifier)) throw new Exception("El identificador " . $identifier . " de " . $name . " (indice " . $this->index . ") está vacío.");
    if(!key_exists($name, $this->import->ids)) $this->import->ids[$name] = [];
    if(in_array($identifier, $this->import->ids[$name])) throw new Exception("El identificador " . $identifier . " de " . $name . " (indice " . $this->index . ") está duplicado.");
    array_push($this->import->ids[$name], $identifier);
  }

  /**
   * Identificar
   * 
   * @return false si ocurrio un error al identificar (consultar logs)
   * @return true si se identifico correctamente
   */
  public function identify($name){
    $identifier = $this->getIdentifier($name);
    if(!key_exists($identifier, $this->import->ids)) $this->import->ids[$name] = [];
    if(!in_array($identifier, $this->import->ids[$name])) array_push($this->import->ids[$name], $identifier);
  }

  


  public function process($name){
    $identifier = $this->getIdentifier($name);
    if(key_exists($identifier, $this->import->dbs[$name])){
      $compare = $this->compare($name, false);
      if(empty($compare)){ 
        $this->logs->addLog($name,"info","Registro existente, campos identicos, no se realizara actualizacion");
        return $this->import->dbs[$name][$identifier]["id"]; 
      } else {
        $compareAux = $this->filterNotNullExistentComparition($name,$compare);
        if(!empty($compareAux)) throw new Exception("El registro $name debe ser actualizado, comparar " . implode(",", $compareAux));
        else return $this->update($name);
      } 
    } 
    return $this->insert($name);
  }

}
