<?php

class RelValue {
  /**
   * controlador para definir el sql join de una entidad
   */

  public $row;
  public $container;
  public $entityName;
  public $value = [];

  public function main($row){
    /**
     * Array asociativo, formato de resultado de consulta sql, ejemplo
     * [ 
     *   id => "v1"
     *   nombre => "v2"
     *   dom_id => "v3"
     *   dom_calle => "v4"
     * ]
     */
    $this->row = $row;
    $tree = $this->container->getEntityTree($this->entityName);
    $this->value[$this->entityName] = $this->container->getValue($this->entityName)->_fromArray($this->row, "set");
    $this->fk($tree);
    return $this->value;
  }

  protected function fk(array $tree){
    foreach ($tree as $prefix => $value) {
      $this->value[$value["field_id"]] = $this->container->getValue($value["entity_name"], $prefix)->_fromArray($this->row, "set");
      if(!empty($value["children"])) $this->fk($value["children"]);
    }
  }

}
