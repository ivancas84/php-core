<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class Ids {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;

  public function main() {
    $display = Filter::jsonPost();
    $render = Render::getInstanceDisplay($display);
    return $this->container->getDb()->ids($this->entityName, $render);    
  }

}
