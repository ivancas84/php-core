<?php
require_once("function/php_input.php");


class LabelAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    
    $display = php_input();
    $rows = $this->container->query($this->entityName)->display($display)->fields(["id","label"])->all();
    return $rows;
  }

}
