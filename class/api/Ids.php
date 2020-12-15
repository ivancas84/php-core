<?php
require_once("function/php_input.php");
require_once("class/model/Render.php");


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
    $render = $this->container->getControllerEntity("render_build", $this->entityName)->main($display);
    return $this->container->getDb()->ids($render->entityName, $render);    
  }

}
