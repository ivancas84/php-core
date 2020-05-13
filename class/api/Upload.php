<?php
require_once("class/controller/Upload.php");
require_once("class/model/Sqlo.php");


class UploadApi {
  /**
   * Api general de upload
   */

  public $entityName;
  /**
   * entityName hace referencia principalmente al tipo de archivo
   * Se definen una serie de tipos,
   * Dependiendo del tipo mime pueden definirse diferente controlador para su procesamiento
   * El tipo por defecto es File (hace referencia a cualquier archivo)
   * Otros tipos pueden ser por ejemplo Image (para imagenes) requiere que se almacenen diferentes tamanios para presentar
   * O puede haber procesamiento especifico de una entidad 
   * En este sentido entityName puede ser por ejemplo Persona, con su procesamiento particular
   */

  public function main() {
    /** 
     * A diferencia de otras apis, la lectura de filtros se realiza dentro del controlador
     */
    try{
      $controller = Upload::getInstanceRequire($this->entityName);
      $data = $controller->main();
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
