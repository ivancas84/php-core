<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class GetAll {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  
  public function main() {
    $ids = Filter::jsonPostRequired(); //siempre deben recibirse ids
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->getDb()->getAll($this->entityName, $ids);
    $sqlo = $this->container->getSqlo($this->entityName);
    foreach($rows as &$row) $row = $sqlo->json($row);
    return $rows;
    
  }

}
