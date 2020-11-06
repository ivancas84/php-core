<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("function/php_input.php");

class AdvancedApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = php_input();
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main($display);
    $rows = $this->container->getDb()->advanced($render->entityName, $render);
    return $rows;
  }

}
