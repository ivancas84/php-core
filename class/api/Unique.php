<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "read";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $params = Filter::jsonPostRequired();
    $row = $this->container->getDb()->unique($this->entityName, $params);
    return $this->container->getRel($this->entityName)->json($row);
  }

}
