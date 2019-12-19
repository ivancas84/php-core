<?php
require_once("class/controller/All.php");
require_once("class/tools/Filter.php");
require_once("class/model/Sqlo.php");


class AllApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      $controller = All::getInstanceRequire($this->entityName);
      $rows = $controller->main($display);
      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->jsonAll($rows));
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
