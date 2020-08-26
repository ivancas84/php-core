<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


abstract class Base {
  /**
   * Controlador base
   * Todos los controladores que no se adaptan a las estructuras existentes pueden definirse con Base
   * El usuario puede retornar el valor que desee 
   * Data esta pensado para ser llamado a traves de una api
   * En el caso de que no se utilice una api, conviene utilizar directamente ModelTools.
   **/
  
  public $entityName; 
  public $container;

  abstract public function main($params);

}
