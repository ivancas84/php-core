<?php
require_once("class/model/Render.php");


class CountApi {
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $display = $this->filterInput();
    $render = Render::getInstanceDisplay($display);
    return $this->container->getDb()->count($this->entityName, $render);
  }

  public function filterInput(){
    $data = file_get_contents("php://input");
    if(!$data) throw new Exception("Error al obtener datos de input");
    return json_decode($data, true);
  }
}
