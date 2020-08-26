<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");

class Ids {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;

  public function main($display) {
    $displayRender = $this->container->getController("display_render", $this->entityName);
    $render = $displayRender->main($display);
    return $this->container->getDb()->ids($this->entityName, $render);    
  }

}
