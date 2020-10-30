<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");


class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    $display = json_decode($data, true);

    $render = Render::getInstanceDisplay($display);
    return $this->container->getDb()->ids($this->entityName, $render);    
  }

}
