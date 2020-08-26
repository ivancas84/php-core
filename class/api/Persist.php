<?php


require_once("class/tools/Filter.php");
require_once("class/Container.php");

class PersistApi {
  public $entityName;

  public function main(){
    try {
      $data = Filter::jsonPostRequired();

      $container = new Container();
      $controller = $container->getController("persist", $this->entityName);
      $result = $controller->main($data);
      echo json_encode($result);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();

    }
  }
}




