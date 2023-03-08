<?php
require_once("function/php_input.php");


class LabelGetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entity_name;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    
    $ids = php_input();
    return $this->container->query($this->entity_name)->fields(["id","label"])->param("id", $ids)->all();
  }

}
