<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");


class GetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "w";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    $ids = json_decode($data, true);

    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->getDb()->getAll($this->entityName, $ids);
    $rel = $this->container->getRel($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
    
  }

}
