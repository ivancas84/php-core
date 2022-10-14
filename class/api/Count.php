<?php

require_once("function/php_input.php");

class CountApi {
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    $display = php_input();
    $render = $this->container->getEntityRender($this->entityName)->setDisplay($display);

    return $this->container->getDb()->count($render->entityName, $render);
  }

}
