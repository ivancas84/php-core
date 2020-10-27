<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class GetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "read";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = Filter::jsonPostRequired(); //siempre deben recibirse ids
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->getDb()->getAll($this->entityName, $ids);
    $rel = $this->container->getRel($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
    
  }

}
