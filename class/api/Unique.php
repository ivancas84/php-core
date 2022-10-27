<?php
require_once("class/model/Ma.php");
require_once("class/model/EntityRender.php");


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
    $row = $this->container->getEntityRender($this->entityName)->unique($params)->fieldTree()->one();
    return $this->container->getEntityTools($render->entityName)->json($row);
  }

}
