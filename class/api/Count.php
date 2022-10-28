<?php

require_once("function/php_input.php");

class CountApi {
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->getAuth()->authorize($this->entityName, $this->permission);
    //$display = php_input();
    $display = [];
    return intval($this->container->query($this->entityName)->display($display)->fields(["count"])->columnOne());
  }

}
