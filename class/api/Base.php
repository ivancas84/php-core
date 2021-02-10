<?php

abstract class BaseApi {
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entityName; 
  public $container;
  public $permission;

  abstract public function main();

}
