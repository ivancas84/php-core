<?php
require_once("class/controller/All.php");
require_once("class/tools/Filter.php");
require_once("class/model/Sqlo.php");


class AllApi {
  /**
   * Api de acceso al controlador All
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired(); //siempre se recibe al menos size y page
      $controller = All::getInstanceRequire($this->entityName);
      $data = $controller->main($display);
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
