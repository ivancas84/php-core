<?php

abstract class BaseController { 
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entityName; 
  public $container;

  abstract public function main();

}
