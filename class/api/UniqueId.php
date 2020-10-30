<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");


class UniqueIdApi {
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    $params = json_decode($data, true);

    $row = $this->container->getDb()->unique($this->entityName, $params);
    return ($row) ? $row["id"] : null;
  }

}
