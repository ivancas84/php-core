<?php

require_once("function/php_input.php");

/**
 * Cantidad de elementos de una entidad
 */
class CountApi {
  public $entity_name;
  public $container;
  public $permission = "r";

  public function main() {
    $this->container->auth()->authorize($this->entity_name, $this->permission);
    $display = php_input();
   
    return intval(
      $this->container->query($this->entity_name)
      ->display($display)
      ->size(0)
      ->page(1)
      ->fields(["count"])
      ->columnOne()
    );
  }

}
