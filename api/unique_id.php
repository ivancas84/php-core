<?php
require_once("function/php_input.php");

 /**
   * Consulta por campos unicos y retornar id
   */
class UniqueIdApi {
 
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);

    $params = php_input();
    $row = $this->container->query($this->entityName)->unique($params)->fieldsTree()->oneOrNull();
    return  ($row) ? $row["id"] : null;
  }

}
