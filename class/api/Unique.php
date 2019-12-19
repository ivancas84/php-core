<?php
require_once("class/controller/Unique.php");
require_once("class/model/Sqlo.php");
require_once("class/tools/Filter.php");

class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    try{
      $params = Filter::jsonPostRequired();
      
      $controller = Unique::getInstanceRequire($this->entityName);
      $row = $controller->main($params);

      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->json($row));

    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
