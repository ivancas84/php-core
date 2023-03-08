<?php
require_once("function/php_input.php");
require_once("function/to_string.php");


class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  public $entity_name;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $display = php_input();
    $ids = $this->container->query($this->entity_name)->display($display)->field("id")->column();
    
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    array_walk($ids, "to_string"); 
    return $ids;
  }

}
