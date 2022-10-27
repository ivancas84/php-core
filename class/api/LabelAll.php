<?php
require_once("class/model/EntityRender.php");
require_once("function/php_input.php");


class LabelAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $display = php_input();
    $rows = $this->container->getEntityRender($this->entityName)->display($display)->fieldAdd(["id","label"])->all();
    return $rows;
  }

}
