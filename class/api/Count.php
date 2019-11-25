<?php
require_once("class/controller/Dba.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

abstract class CountApi {
  protected $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      $render = RenderAux::getInstanceDisplay($display);
      echo json_encode(Dba::count($this->entityName, $render));
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }
}
