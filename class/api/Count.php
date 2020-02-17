<?php
require_once("class/controller/Count.php");

require_once("class/model/Ma.php");
require_once("class/model/Render.php");
require_once("class/tools/Filter.php");

class CountApi {
  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPostRequired();

      $controller = Count::getInstanceRequire($this->entityName);
      $count = $controller->main($display);
      echo json_encode($count);

    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }
}
