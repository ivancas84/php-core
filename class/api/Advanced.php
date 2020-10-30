<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");


class AdvancedApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
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
    $rows = $this->container->getDb()->advanced($this->entityName, $render);
    return $rows;
  }

}
