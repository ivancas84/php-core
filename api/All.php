<?php

require_once("function/php_input.php");

/**
 * consulta de una entidad, retorna todos los campos del arbol y sus relaciones
 */
class AllApi {
  

  public $container;
  public $entity_name;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    $display = php_input();
    $rows = $this->container->query($this->entity_name)->display($display)->fieldsTree()->all();
    return $this->container->tools($this->entity_name)->jsonAll($rows);
  }

}
