<?php

abstract class BaseApi {
  /**
   * Controlador base
   * Elementos en comun a todos los controladores
   **/
  
  public $entityName; 
  public $container;

  abstract public function main();

}
