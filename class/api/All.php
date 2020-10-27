<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class AllApi {
  /**
   * Obtener todos los datos de una determinada entidad   
   */

  public $entityName;
  public $container;
  public $permission = "read";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = Filter::jsonPostRequired(); //siempre se recibe al menos size y page
    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->all($this->entityName, $render);
    $rel = $this->container->getRel($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
  }


}
