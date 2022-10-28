<?php
require_once("function/php_input.php");
require_once("class/model/EntityQuery.php");


class UniqueIdApi {
  /**
   * Consulta por campos unicos y retornar id
   */
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $params = php_input();
    $render = $this->container->query($this->entityName);
    try {
      $row = $this->container->getDb()->unique($render->entityName, $params);
      return ($row) ? $row["id"] : null;
    } catch (Exception $ex) {
      return null;
    }
  }

}
