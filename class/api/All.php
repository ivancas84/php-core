<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("function/php_input.php");

class AllApi {
  /**
   * Obtener todos los datos de una determinada entidad   
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    $display = json_decode($data, true); //siempre se recibe al menos size y page

    $render = Render::getInstanceDisplay($display);
    $rows = $this->container->getDb()->all($this->entityName, $render);
    $rel = $this->container->getRel($this->entityName);
    foreach($rows as &$row) $row = $rel->json($row);
    return $rows;
  }


}
