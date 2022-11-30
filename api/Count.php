<?php

require_once("function/php_input.php");

/**
 * Cantidad de elementos de una entidad
 */
class CountApi {
  public $entityName;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entityName, $this->permission);
    $display = php_input();
   
    return intval(
      $this->container->query($this->entityName)
      ->display($display)
      ->size(0)
      ->page(1)
      ->fields(["count"])
      ->columnOne()
    );
  }

}
