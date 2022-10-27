<?php
require_once("class/model/EntityRender.php");
require_once("function/php_input.php");


class LabelGetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;
  public $container;
  public $permission = "r";
  
  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    
    $ids = php_input();
    return $this->container->getEntityRender($this->entityName)->fieldAdd(["id","label"])->param("id", $ids)->all();
  }

}
