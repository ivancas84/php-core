<?php
require_once("function/php_input.php");
require_once("class/model/EntityRender.php");
require_once("function/to_string.php");


class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = php_input();
    $ids = $this->container->getEntityRender($this->entityName)->display($display)->fieldAdd(["id"])->column();
    
    /**
     * los ids son tratados como string para evitar un error que se genera en Angular (se resta un numero en los enteros largos)
     */
    array_walk($ids, "to_string"); 
    return $ids;
  }

}
