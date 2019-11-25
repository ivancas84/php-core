<?php
require_once("class/controller/Dba.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class Count {
  protected $entityName;

  /**
   * Comportamiento general de count
   */
  final public static function getInstance() {
    $className = get_called_class();
    return new $className;
  }

  final public static function getInstanceRequire($entity) {
    require_once("class/controller/count/" . snake_case_to("XxYy", $entity) . ".php");
    $className = snake_case_to("XxYy", $entity) . "Count";
    return call_user_func("{$className}::getInstance");
  }
  
  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      $render = RenderAux::getInstanceDisplay($display);
      echo json_encode(Dba::count($this->entityName, $render));
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }
}
