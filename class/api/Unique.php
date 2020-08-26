<?php
require_once("class/Container.php");
require_once("class/tools/Filter.php");

class UniqueApi {
  /**
   * Comportamiento general de all
   */

  public $entityName;

  public function main() {
    try{
      $params = Filter::jsonPostRequired();
      
      $container = new Container();
      $controller = $container->getController("unique", $this->entityName);
      $row = $controller->main($params);
      echo json_encode($row);

    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
