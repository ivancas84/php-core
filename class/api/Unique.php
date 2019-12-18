<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    try{
      $params = Filter::jsonPostRequired();
      //$params = ["domicilio"=>"1543133270054093"];
      $row = Ma::unique($this->entityName, $params);
      echo json_encode(EntitySqlo::getInstanceRequire($this->entityName)->json($row));

    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
