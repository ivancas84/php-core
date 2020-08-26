<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");

class Count {
  public $entityName;

  public function main($display) {
    $displayRender = $this->container->getController("display_render", $this->entityName);
    $render = $displayRender->main($display);
    return $this->container->getDb()->count($this->entityName, $render);
  }
}
