<?php
require_once("class/model/RenderPlus.php");

class DisplayRender {
  /**
   * Transformar parametros (display) en presentacion (render)
   */

  public $entityName;

  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceString($entity) {
    $className = snake_case_to("XxYy", $entity) . "DisplayRender";
    return call_user_func("{$className}::getInstance");
  }

  final public static function getInstanceRequire($entity){
    require_once("class/controller/displayRender/" . snake_case_to("XxYy", $entity) . ".php");
    return self::getInstanceString($entity);
  }

  public function main($display) {
    return RenderPlus::getInstanceDisplay($display);
  }

}
