<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class IdsApi {
  /**
   * Comportamiento general de api ids
   */

  protected $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      //$display = ["condition" => ["id","=",[18,35]]]; //prueba
      $render = RenderAux::getInstanceDisplay($display);
      $ids = Ma::ids($this->entityName, $render);
      echo json_encode($ids);
    
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }

}
