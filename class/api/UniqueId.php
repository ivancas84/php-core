<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class UniqueIdApi {
  public $entityName;
  public $container;
  public $permission = "read";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $params = Filter::jsonPostRequired();
    $row = $this->container->getDb()->unique($this->entityName, $params);
    return ($row) ? $row["id"] : null;
  }

}
