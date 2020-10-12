<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class AllApi {
  /**
   * Obtener todos los datos de una determinada entidad
   * Cada elemento de datos generalmente corresponde a una entidad simple, 
   * cuyos datos derivados se calculan con elementos directos
   * Si los datos derivados dependen de un conjunto avanzado de relaciones,
   * debe utilizarse la clase BaseApi
   */

  public $entityName;
  public $container;

  public function main() {
    $display = Filter::jsonPostRequired(); //siempre se recibe al menos size y page
    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->all($this->entityName, $render);
    $rel = $this->container->getRel($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
  }

}
