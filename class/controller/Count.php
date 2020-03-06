<?php
require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");
require_once("class/controller/DisplayRender.php");

class Count {
  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "Count";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/count/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  public function main($display) {
    $displayRender = DisplayRender::getInstanceRequire($this->entityName);
    $render = $displayRender->main($display);
    return Ma::count($this->entityName, $render);
  }
}
