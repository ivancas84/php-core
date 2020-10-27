<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;
  public $container;
  public $permission = "read";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = Filter::jsonPost();
    $render = Render::getInstanceDisplay($display);
    return $this->container->getDb()->ids($this->entityName, $render);    
  }

}
