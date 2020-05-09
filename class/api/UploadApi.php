<?php
require_once("class/controller/Upload.php");
require_once("class/model/Sqlo.php");


class UploadApi {
  /**
   * Api general de upload
   */

  public $type;
  /**
   * dependiendo del tipo mime pueden definirse diferente procesamiento
   * El tipo por defecto es File (hace referencia a cualquier archivo)
   */

  public function main() {
    /** 
     * A diferencia de otras apis, la lectura de filtros se realiza dentro del controlador
     */
    try{
      $controller = Upload::getInstanceRequire($this->type);
      $data = $controller->main();
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
