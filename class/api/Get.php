<?php
require_once("class/model/EntityQuery.php");
require_once("function/php_input.php");


class GetApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $id = file_get_contents("php://input");
    if(empty($id)) throw new Exception("Identificador no definido");
    $row = $this->container->query($this->entityName)->fieldTree()->param("id",$id)->one();
    return $this->container->getEntityTools($this->entityName)->json($row);
  }

}
