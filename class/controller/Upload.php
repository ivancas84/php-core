<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


class Upload {
  /**
   * Obtener todos los datos de una determinada entidad
   */

  public $type;
  
  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $type) . "Upload";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/upload/" . snake_case_to("XxYy", $type) . ".php");
    return self::getInstanceString($type);
  }

  public function main() {
    

  }

}
