<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

abstract class FurtherErrorApi {
  /**
   * Consulta de errores avanzados de una entidad
   * Devuelve un par key:value con el primer error encontrado o null si no hay errores
   * Se define de manera abstracta, se invoca de forma consciente por lo tanto requiere redefinicion
   */

  public $entityName;
  public $container;
  public $permission = "read";

  abstract public function main();

}
