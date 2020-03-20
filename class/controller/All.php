<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");


class All {
  /**
   * Obtener todos los datos de una determinada entidad
   */

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
    require_once("class/controller/all/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  public function main($display) {
    $displayRender = DisplayRender::getInstanceRequire($this->entityName);
    $render = $displayRender->main($display);
    $rows = Ma::all($this->entityName, $render);
    return EntitySqlo::getInstanceRequire($this->entityName)->jsonAll($rows);
  }

}
