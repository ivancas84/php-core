<?php
require_once("class/controller/Dba.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class UniqueApi {
  /**
   * Comportamiento general de all
   */

  protected $entityName;

  public function main() {
    try{
      $params = Filter::jsonPostRequired();
      //$params = ["domicilio"=>"1543133270054093"];
      $row = Dba::unique($this->entityName, $params);
      echo json_encode($row);

    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
