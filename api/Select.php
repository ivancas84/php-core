<?php
require_once("function/php_input.php"); 

class SelectApi {
  /**
   * Consultas avanzadas a una entidad
   * Las consultas avanzadas pueden acceder a campos no habituales
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    
    $display = php_input();
    return $this->container->query($this->entityName)->display($display)->all();
  }

}
