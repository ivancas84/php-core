<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


abstract class Data {
  /**
   * Obtener todos los datos de una determinada entidad
   * Data es un controlador abstracto
   * el usuario puede retornar el valor que desee 
   * Data esta pensado para ser llamado a traves de una api
   * En el caso de que no se utilice una api, conviene utilizar directamente ModelTools
   **/

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "All";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/data/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  abstract public function main($display);

}
