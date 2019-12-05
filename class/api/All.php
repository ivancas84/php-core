<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class AllApi {
  /**
   * Comportamiento general de all
   */

  protected $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      $render = RenderAux::getInstanceDisplay($display);
      $rows = Ma::all($this->entityName, $render);
      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->jsonAll($rows));
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
