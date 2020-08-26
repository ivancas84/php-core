<?php
require_once("class/tools/Filter.php");
require_once("class/Container.php");

class BaseApi {
  /**
   * Api de acceso al controlador Base
   */

  public $entityName;

  public function main() {
    try{
      $display = Filter::jsonPost();

      $container = new Container();
      $controller = $container->getController("base", $this->entityName);
      $data = $controller->main($display);
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
