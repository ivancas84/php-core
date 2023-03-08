<?php
require_once("function/php_input.php");


class LabelAllApi {
  /**
   * Comportamiento general de all
   */

  public $entity_name;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $display = php_input();
    $rows = $this->container->query($this->entity_name)->display($display)->fields(["id","label"])->all();
    return $rows;
  }

}
