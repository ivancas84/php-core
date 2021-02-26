<?php

require_once("function/get_entity_tree.php");

class RelJson {
  /**
   * controlador para definir el json de una entidad y sus relaciones
   */

  public $row;
  public $container;
  public $entityName;
  public $json = [];

  public function main($row){
    $this->row = $row;
    $tree = get_entity_tree($this->entityName);
    if(empty($this->row)) return null;
    $this->json = $this->container->getValue($this->entityName)->_fromArray($this->row, "set")->_toArray("json");
    $this->fk($tree, $this->json);
    return $this->json;
  }

  protected function fk(array $tree, &$json){
    foreach ($tree as $prefix => $value) {
      if(!is_null($this->row[$prefix.'_id'])) {        
        $json[$value["field_name"]."_"] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($this->row, "set")->_toArray("json");
        if(!empty($value["children"])) $this->fk($value["children"], $json[$value["field_name"]."_"]);
      }
      
    }
  }

}
