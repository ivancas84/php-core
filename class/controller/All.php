<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


class All {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $entityName;
  public $db;
  public $container;

  public function main($display) {
    $displayRender = $this->container->getController("display_render", $this->entityName);
    $render = $displayRender->main($display);
    $rows = $this->db->all($this->entityName, $render);
    $sqlo = $this->container->getSqlo($this->entityName);
    foreach($rows as &$row) $row = $sqlo->json($row);
    return $rows;
  }

}
