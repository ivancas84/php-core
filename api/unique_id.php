<?php
require_once("function/php_input.php");

 /**
   * Consulta por campos unicos y retornar id
   */
class UniqueIdApi {
 
  public $entity_name;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);

    $params = php_input();
    $row = $this->container->query($this->entity_name)->unique($params)->fieldsTree()->oneOrNull();
    return  ($row) ? $row["id"] : null;
  }

}
