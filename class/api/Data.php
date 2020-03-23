<?php
require_once("class/controller/Data.php");
require_once("class/tools/Filter.php");
require_once("class/model/Sqlo.php");


class DataApi {
  /**
   * Api general de data
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPost();
      $controller = Data::getInstanceRequire($this->entityName);
      $data = $controller->main($display);
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
