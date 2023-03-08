<?php

require_once("function/snake_case_to.php");
require_once("function/settypebool.php");
require_once("function/array_add_prefix.php");

/**
 * Metodos adicionales definidos a partir de relaciones entre entidades
 */
class EntityTools {
  public $container;
  public $entity_name;

  /**
   * Array de nombres de campos definidos entidad principal y sus relaciones, de la forma fieldId-field_name
   */
  public function field_names(){
    $field_names = $this->container->entity($this->entity_name)->getFieldNames();
    foreach($this->container->relations($this->entity_name) as $prefix => $value){
      $field_names = array_unique(
        array_merge(
          $field_names, 
          array_add_prefix(
            $this->container->entity($value["entity_name"])->getFieldNames(),
            $prefix."-"
          )
        )
      );
    }
    return $field_names;
  }


  public function json($row){
    $tree = $this->container->tree($this->entity_name);
    if(empty($row)) return null;
    $this->json = $this->container->value($this->entity_name)->_fromArray($row, "set")->_toArray("json");
    $this->jsonFk($tree, $this->json, $row);
    return $this->json;
  }

  protected function jsonFk(array $tree, &$json, &$row){
    foreach ($tree as $prefix => $value) {
      if(!is_null($row[$prefix.'-id'])) {   
        $json[$value["field_name"]."_"] = $this->container->value($value["entity_name"], $prefix)->_fromArray($row, "set")->_toArray("json");
        if(!empty($value["children"])) $this->jsonFk($value["children"], $json[$value["field_name"]."_"], $row);
      }
    }
  }

  public function json2($row){
    $json = [];
    $tree = $this->container->tree($this->entity_name);
    $json[$this->entity_name] = $this->container->value($this->entity_name)->_fromArray($row, "set")->_toArray("json");
    $this->json2Fk($tree, $row, $json);
    return $json;
  }

  protected function json2Fk(array $tree, $row, &$json){
    foreach ($tree as $prefix => $value) {
      $json[$prefix] = $this->container->value($value["entity_name"], $prefix)->_fromArray($row, "set")->_toArray("json");
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
    $tree = $this->container->tree($this->entity_name);
    $value[$this->entity_name] = $this->container->value($this->entity_name)->_fromArray($row, "set");
    $this->valueFk($tree, $row, $value);
    return $value;
  }

  protected function valueFk(array $tree, $row, &$value){
    foreach ($tree as $fieldId => $subtree) {
      $value[$fieldId] = $this->container->value($subtree["entity_name"], $fieldId)->_fromArray($row, "set");
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
