<?php
require_once("class/model/Render.php");
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
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main(null);
    if(empty($id)) throw new Exception("Identificador no definidos");
    $row = $this->container->getDb()->get($render->entityName, $id);
    return $this->container->getRel($render->entityName)->json($row);
  }

}
