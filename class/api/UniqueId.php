<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class UniqueIdApi {
  public $entityName;

  public function main() {
    $params = Filter::jsonPostRequired();
    $row = $this->container->getDb()->unique($this->entityName, $params);
    return ($row) ? $row["id"] : null;
  }

}
