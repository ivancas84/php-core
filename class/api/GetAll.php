<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class GetAllApi {
  /**
   * Comportamiento general de all
   */

  protected $entityName;

  public function main() {
    try{
      $ids = Filter::jsonPostRequired();
      if(empty($ids)) throw new Exception("Identificadores no definidos");
      $rows = Ma::getAll($this->entityName, $ids);
      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->jsonAll($rows));
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
