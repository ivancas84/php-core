<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");
require_once("function/get_entity_relations.php");


class EntityRel {
  /**
   * Metodos adicionales definidos a partir de relaciones entre entidades
   */

  public $container;
  public $entityName;

  public function mapping($field){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_relations($this->entityName)[$f[0]];
      if($r = $this->container->getMapping($entityName, $prefix)->_($f[1])) return $r;
    } 
    if($f = $this->container->getMapping($this->entityName)->_($field)) return $f;
    throw new Exception("Campo no reconocido para {$this->entityName}: {$field}");
  }
  
  public function condition($field, $option, $value){
    /**
     * Condicion avanzada principal
     * Define una condicion avanzada que recorre todos los metodos independientes de condicion avanzadas de las tablas relacionadas
     * La restriccion de conditionFieldStruct es que $value no puede ser un array, ya que definirá un conjunto de condiciones asociadas
     */
    
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_relations($this->entityName)[$f[0]];
      return $this->container->getCondition($entityName, $prefix)->_($f[1], $option, $value);
    } 
    return $this->container->getCondition($this->entityName)->_($field, $option, $value);
  }
  
  public function conditionAux($field, $option, $value) {
    /**
     * Condicion de field auxiliar
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = $f[0];
      $entityName = get_entity_relations($this->entityName)[$f[0]];
      if($c = $this->container->getConditionAux($entityName, $prefix)->_($f[1], $option, $value)) return $c;
    } 
    if($c = $this->container->getConditionAux($this->entityName)->_($field, $option, $value)) return $c;
  }


  public function fields(){
    /**
     * Definir sql de campos     
     */
    $fields = [implode(",", $this->container->getFieldAlias($this->entityName)->_toArray())];
    foreach(get_entity_relations($this->entityName) as $prefix => $entityName) 
      array_push($fields, implode(", ", $this->container->getFieldAlias($entityName, $prefix)->_toArray()));
    
    return implode(',
', $fields);
  }
  
  public function fieldAlias($field){
    $f = explode("-",$field);
    if(count($f) == 2) {      
      $prefix = $f[0];
      $entityName = get_entity_relations($this->entityName)[$f[0]];
      return $this->container->getFieldAlias($this->entityName, $prefix)->_($f[1]);
    } 
    return $this->container->getFieldAlias($this->entityName)->_($field);
  }

  public function join(Render $render){
    return $this->container->getRelJoin($this->entityName)->main($render);
  }

  public function json($row){
    return $this->container->getRelJson($this->entityName)->main($row);
  }

  public function value($row){
    /**
     * Recorre la cadena de relaciones del resultado de una consulta y retorna instancias de EntityValues
     * El resultado es almacenado en un array asociativo.
     * Las claves del array son identificadores unicos representativos del nombre del campo
     * Las claves se forman a partir del nombre de la clave foranea
     * Se asigna un numero incremental a la clave en el caso de que se repita
     * A diferencia de otros métodos que retornan valores, 
     * values utiliza un array asociativo debido a que el valor es un objeto
     * facilita el acceso directo desde la llave por ejemplo $resultado["nombre_fk"]->metodo()
     * En el caso por ejemplo del metodo json, debido a que el valor es tambien un un array asociativo, 
     * tiene sentido acomodarlo como un arbol de valores, identificandolos con el prefijo "_",
     * por ejemplo $resultado["nombre_fk] = "id_fk"
     * $resultado["_nombre_fk"] = array asociativo con los valores de la entidad para el id "id_fk"
     */
    return $this->container->getRelValue($this->entityName)->main($row);
  }
}
