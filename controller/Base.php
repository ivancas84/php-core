<?php

abstract class BaseController { 
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entity_name; 
  public $container;

  abstract public function main();

}
