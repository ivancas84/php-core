<?php


require_once("class/tools/Filter.php");
require_once("class/controller/Transaction.php");
require_once("class/controller/Persist.php");

require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

class PersistApi {
  public $entityName;

  public function main(){
    try {
      $data = Filter::jsonPostRequired();
      $persistController = Persist::getInstanceRequire($this->entityName);
      $result = $persistController->main($data);
      echo json_encode($result);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();

    }
  }
}




