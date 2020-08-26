<?php
require_once("class/controller/Count.php");

require_once("class/Container.php");
require_once("class/tools/Filter.php");

class CountApi {
  /**
   * Api de acceso al controlador Count
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPost();

      $container = new Container();
      $controller = $container->getController("count", $this->entityName);
      $count = $controller->main($display);
      echo json_encode($count);

    } catch (Exception $ex) {
      http_response_code(500);
      error_log($ex->getTraceAsString());
      echo $ex->getMessage();
    }
  }
}
