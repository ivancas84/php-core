<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderPlus.php");
require_once("class/tools/Filter.php");

class GetAll {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "GetAll";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/getAll/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  public function main($ids) {
    if(empty($ids)) throw new Exception("Identificadores no definidos");
    return Ma::getAll($this->entityName, $ids);
  }

}
