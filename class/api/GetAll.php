<?php
require_once("class/controller/GetAll.php");
require_once("class/model/Sqlo.php");
require_once("class/tools/Filter.php");

class GetAllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    try{
      $ids = Filter::jsonPostRequired();
      $controller = GetAll::getInstanceRequire($this->entityName);
      $rows = $controller->main($ids);
      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->jsonAll($rows));
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
