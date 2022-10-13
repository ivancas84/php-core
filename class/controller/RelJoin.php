<?php

class RelJoin {
  /**
   * controlador para definir el sql join de una entidad
   */

  public $render;
  public $container;
  public $entityName;
  public $sql = "";

  public function main($render){
    $this->render = $render;
    $tree = $this->container->getEntityTree($this->entityName);
    $this->fk($tree, "");
    return $this->sql;
  }

  protected function fk(array $tree, $tablePrefix){
    if (empty ($tablePrefix)) $tablePrefix = $this->container->getEntity($this->entityName)->getAlias();

    foreach ($tree as $prefix => $value) {      
      $this->sql .= $this->container->getSql($value["entity_name"], $prefix)->_join($value["field_name"], $tablePrefix, $this->render) . "
";

      if(!empty($value["children"])) $this->fk($value["children"], $prefix);
    }
  }

}
