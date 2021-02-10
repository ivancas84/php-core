<?php
require_once("class/model/Render.php");
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
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main(null);
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    $rows = $this->container->getDb()->getAll($render->entityName, $ids);
    $rel = $this->container->getRel($render->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
    
  }

}
