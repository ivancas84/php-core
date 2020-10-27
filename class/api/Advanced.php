<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class AdvancedApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
   */

  public $entityName;
  public $container;
  public $permission = "read";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = Filter::jsonPostRequired();
    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->advanced($this->entityName, $render);
    return $rows;
  }

}
