<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

class Ids {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Ids";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/ids/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }


  public function main($display) {
    $render = RenderAux::getInstanceDisplay($display);
    return Ma::ids($this->entityName, $render);    
  }

}
