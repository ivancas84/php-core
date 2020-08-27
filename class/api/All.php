<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class All {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $entityName;
  public $container;

  public function main() {
    $display = Filter::jsonPostRequired(); //siempre se recibe al menos size y page
    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->all($this->entityName, $render);
    $sqlo = $this->container->getSqlo($this->entityName);
    foreach($rows as &$row) $row = $sqlo->json($row);
    return $rows;
  }

}
