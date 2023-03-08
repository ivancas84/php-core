<?php
require_once("function/php_input.php"); 

class SelectApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
   */

  public $entity_name;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $display = php_input();
    return $this->container->query($this->entity_name)->display($display)->all();
  }

}
