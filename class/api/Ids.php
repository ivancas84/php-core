<?php
require_once("class/controller/Ids.php");
require_once("class/tools/Filter.php");

class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();

      $controller = Ids::getInstanceRequire($this->entityName);
      $ids = $controller->main($display);

      echo json_encode($ids);
    
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }

}
