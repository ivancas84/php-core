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
      $ids = Filter::jsonPostRequired(); //siempre deben recibirse ids

      $container = new Container();
      $controller = $container->getController("get_all", $this->entityName);
      $rows = $controller->main($ids);
      echo json_encode($rows);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
