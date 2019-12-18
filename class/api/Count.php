<?php
require_once("class/model/Ma.php");
require_once("class/model/RenderAux.php");
require_once("class/tools/Filter.php");

class CountApi {
  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();
      $render = RenderAux::getInstanceDisplay($display);
      echo json_encode(Ma::count($this->entityName, $render));
    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }
}
