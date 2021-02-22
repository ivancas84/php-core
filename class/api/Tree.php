<?php

require_once("function/get_entity_tree.php");

class TreeApi {
  /**
   * Comportamiento general de persistencia
   */

  public $entityName;
  public $container;
  public $permission = "w";

  public function main(){
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    return get_entity_tree($this->entityName);    
  }
}



