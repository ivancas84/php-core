<?php
require_once("class/model/EntityQuery.php");
require_once("function/php_input.php");


class GetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = php_input();
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->query($this->entityName)->param("id",$ids)->fieldTree()->all();
    $rel = $this->container->getEntityTools($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
    
  }

}
