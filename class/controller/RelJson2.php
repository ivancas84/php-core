<?php

require_once("function/get_entity_tree.php");

class RelJson2 {
  /**
   * controlador para definir el json de una entidad y sus relaciones
   * No define un arbol como RelJson, almacena los cambios en un array asociativo de prefijos
   * A diferencia de RelJson, no retorna null si no estan definidos los valores, retorna un array de llaves con elementos vacios
   * @example Ejemplo de retorno para la entidad alumno
   * [
   *   "alumno" => [
   *     "id" => "...",
   *     "activo" => false,
   *   ],
   *   "per"=> [
   *     "id" => "..."
   *     "numero_documento" > "..."
   *   ]
   *   "per_dom" => [ ... ] 
   * ]
   */

  public $row;
  public $container;
  public $entityName;
  public $json = [];
  public $prefix = "";

  public function main($row){
    $this->row = $row;
    $tree = get_entity_tree($this->entityName);
    $id = (empty($this->prefix)) ? $this->entityName : $this->prefix;
    $this->json[$id] = $this->container->getValue($this->entityName)->_fromArray($this->row, "set")->_toArray("json");
    $this->fk($tree);
    return $this->json;
  }

  protected function fk(array $tree){
    foreach ($tree as $prefix => $value) {
      $id = (empty($this->prefix)) ? $prefix : $this->prefix . "_".$prefix;
      $this->json[$id] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($this->row, "set")->_toArray("json");
      if(!empty($value["children"])) $this->fk($value["children"]);
    }
  }

}
