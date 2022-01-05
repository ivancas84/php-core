<?php

require_once("class/model/Render.php");
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
    $render = $this->container->getRender($this->entityName)->setDisplay($display);
    $rows = $this->container->getDb()->all($render->entityName, $render);
    return $this->container->getRel($render->entityName)->jsonAll($rows);
  }

}
