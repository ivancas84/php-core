<?php

require_once("function/snake_case_to.php");
require_once("class/model/Sql.php");
require_once("class/model/Render.php");
require_once("function/settypebool.php");
require_once("function/array_add_prefix.php");

class EntityRel {
  /**
   * Metodos adicionales definidos a partir de relaciones entre entidades
   */

  public $container;
  public $entityName;
  public $prefix = "";


  public function mapping($field){
    /**
     * Traducir campo para ser interpretado correctamente por el SQL
     */
    $f = explode("-",$field);
    if(count($f) == 2) {
      $prefix = (empty($this->prefix)) ? $f[0] : $this->prefix . "_" . $f[0];
      $entityName = $this->container->getEntityRelations($this->entityName)[$f[0]]["entity_name"];
      if($r = $this->container->getMapping($entityName, $prefix)->_($f[1])) return $r;
    } 
    if($f = $this->container->getMapping($this->entityName, $this->prefix)->_($field)) return $f;
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
      $entityName = $this->container->getEntityRelations($this->entityName)[$f[0]]["entity_name"];
      return $this->container->getCondition($entityName, $prefix)->_($f[1], $option, $value);
    } 
    return $this->container->getCondition($this->entityName)->_($field, $option, $value);
  }
  
  /**
   * Array de nombres de campos definidos entidad principal y sus relaciones, de la forma fieldId-fieldName
   */
  public function fieldNames(){
    $fieldNames = $this->container->getEntity($this->entityName)->getFieldNames();
    foreach($this->container->getEntityRelations($this->entityName) as $prefix => $value){
      $fieldNames = array_unique(
        array_merge(
          $fieldNames, 
          array_add_prefix(
            $this->container->getEntity($value["entity_name"])->getFieldNames(),
            $prefix."-"
          )
        )
      );
    }
    return $fieldNames;
  }


  public function join($render){
    $sql = "";
    $tree = $this->container->getEntityTree($this->entityName);
    $this->joinFk($tree, "", $sql, $render);
    return $sql;
  }

  protected function joinfk(array $tree, $tablePrefix, &$sql, Render $render){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->getEntity($this->entityName)->getAlias();

    foreach ($tree as $prefix => $value) {      
      $sql .= $this->container->getSql($value["entity_name"], $prefix)->_join($value["field_name"], $tablePrefix, $render) . "
";

      if(!empty($value["children"])) $this->joinfk($value["children"], $prefix, $sql, $render);
    }
  }

  public function json($row){
    $tree = $this->container->getEntityTree($this->entityName);
    if(empty($row)) return null;
    $this->json = $this->container->getValue($this->entityName)->_fromArray($row, "set")->_toArray("json");
    $this->jsonFk($tree, $this->json, $row);
    return $this->json;
  }

  protected function jsonFk(array $tree, &$json, &$row){
    foreach ($tree as $prefix => $value) {
      if(!is_null($row[$prefix.'_id'])) {   
        $json[$value["field_name"]."_"] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($row, "set")->_toArray("json");
        if(!empty($value["children"])) $this->jsonFk($value["children"], $json[$value["field_name"]."_"], $row);
      }
    }
  }


  public function json2($row){
    $json = [];
    $tree = $this->container->getEntityTree($this->entityName);
    $id = (empty($this->prefix)) ? $this->entityName : $this->prefix;
    $json[$id] = $this->container->getValue($this->entityName)->_fromArray($row, "set")->_toArray("json");
    $this->json2Fk($tree, $row, $json);
    return $json;
  }

  protected function json2Fk(array $tree, $row, &$json){
    foreach ($tree as $prefix => $value) {
      $id = (empty($this->prefix)) ? $prefix : $this->prefix . "_".$prefix;
      $json[$id] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($row, "set")->_toArray("json");
      if(!empty($value["children"])) $this->json2Fk($value["children"], $row, $json);
    }
  }

   /**
     * Recorre la cadena de relaciones del resultado de una consulta y retorna instancias de EntityValues
     * El resultado es almacenado en un array asociativo.
     * Las claves del array son identificadores unicos representativos del nombre del campo
     * Las claves se forman a partir del nombre de la clave foranea (se extrae de las funciones de identificacion)
     * Se asigna un numero incremental a la clave en el caso de que se repita
     * A diferencia de otros métodos que retornan valores,  
     * values utiliza un array asociativo debido a que el valor es un objeto
     * facilita el acceso directo desde la llave por ejemplo $resultado["nombre_fk"]->metodo()
     * En el caso por ejemplo del metodo json, debido a que el valor es tambien un array asociativo, 
     * tiene sentido acomodarlo como un arbol de valores, identificandolos con el prefijo "_",
     * por ejemplo $resultado["nombre_fk] = "id_fk"
     * $resultado["_nombre_fk"] = array asociativo con los valores de la entidad para el id "id_fk"
     */
  public function value($row){
    $value = [];
    $tree = $this->container->getEntityTree($this->entityName);
    $value[$this->entityName] = $this->container->getValue($this->entityName)->_fromArray($row, "set");
    $this->valueFk($tree, $row, $value);
    return $value;
  }

  protected function valueFk(array $tree, $row, &$value){
    foreach ($tree as $fieldId => $subtree) {
      $value[$fieldId] = $this->container->getValue($subtree["entity_name"], $fieldId)->_fromArray($row, "set");
      if(!empty($subtree["children"])) $this->valueFk($subtree["children"], $row, $value);
    }
  }

  public function jsonAll($rows){
    foreach($rows as &$row) $row = $this->json($row);
    return $rows;
  }

  public function json2All($rows){
    foreach($rows as &$row) $row = $this->json2($row);
    return $rows;
  }


  public function valueAll($rows){
    foreach($rows as &$row) $row = $this->value($row);
    return $rows;
  }

}
