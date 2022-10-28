<?php

require_once("function/php_input.php");

/**
 * consulta de una entidad, retorna todos los campos del arbol y sus relaciones
 */
class AllApi {
  

  public $container;
  public $entityName;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    $display = php_input();
    $rows = $this->container->query($this->entityName)->display($display)->fieldsTree()->all();
    return $this->container->tools($this->entityName)->jsonAll($rows);
  }

}
