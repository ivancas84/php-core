<?php

require_once("function/get_entity_tree.php");

class RelValue {
  /**
   * controlador para definir el sql join de una entidad
   */

  public $row;
  public $container;
  public $entityName;
  public $value = [];

  public function main($row){
    $this->row = $row;
    $tree = get_entity_tree($this->entityName);
    $this->value[$this->entityName] = $this->container->getValue($this->entityName)->_fromArray($this->row, "set");
    $this->fk($tree, $this->value);
    return $this->value;
  }

  protected function fk(array $tree){
    foreach ($tree as $prefix => $value) {
      $this->value[$value["field_id"]] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($this->row, "set");
      if(!empty($value["children"])) $this->fk($value["children"]);
    }
  }

}
