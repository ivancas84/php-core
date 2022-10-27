<?php

require_once("class/model/EntityRender.php");
require_once("function/php_input.php");

class AllApi {
  /**
   * Obtener todos los datos de una determinada entidad   
   */

  public $container;
  public $entityName; //entidad principal (real o ficticia)
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $display = php_input();
    $rows = $this->container->getEntityRender($this->entityName)->display($display)->fieldTree()->all();
    return $this->container->getEntityTools($this->entityName)->jsonAll($rows);
  }

}
