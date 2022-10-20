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
    $render = $this->container->getEntityRender($this->entityName)->setDisplay($display);
    $render->setFields(["id","label"]);
    $rows = $this->container->getDb()->select($this->entityName, $render);
    return $rows;
  }

}
