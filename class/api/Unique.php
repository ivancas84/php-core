<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");


class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $params = php_input();
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main($display);

    $row = $this->container->getDb()->unique($render->entityName, $params);
    return $this->container->getRel($render->entityName)->json($row);
  }

}
