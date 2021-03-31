<?php

abstract class BaseApi { //1.1
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entityName; 
  public $container;
  public $permission = "r";

  abstract public function main();

}
