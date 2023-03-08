<?php

/**
   * Consulta de errores avanzados de una entidad
   * Devuelve un par key:value con el primer error encontrado o null si no hay errores
   * Se define de manera abstracta, se invoca de forma consciente por lo tanto requiere redefinicion
   */
abstract class FurtherErrorApi {
  public $entity_name;
  public $container;
  public $permission = "r";

  abstract public function main();

}
