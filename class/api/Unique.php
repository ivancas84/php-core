<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    $params = Filter::jsonPostRequired();
    $row = $this->container->getDb()->unique($this->entityName, $params);
    return $this->container->getSqlo($this->entityName)->json($row);
  }

}
