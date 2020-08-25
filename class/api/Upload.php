<?php
require_once("class/controller/Upload.php");
require_once("class/model/Sqlo.php");


class UploadApi {
  /**
   * Api general de upload para procesar un solo archivos
   */

  public $entityName;
  /**
   * entityName hace referencia principalmente al tipo de procesamiento, por ejemplo "file", "image" o algun procesamiento particular.
   * entityName por defecto hace referencia al tipo mime pero no es estrictamente necesario, puede referirse también a un modo particular de procesamiento.
   * Habitualmente se define un controlador por defecto denominado File, que hace referencia a cualquier archivo
   * Otros tipos de procesamiento pueden ser por ejemplo Image (para imagenes) requiere que se almacenen diferentes tamanios para presentar
   * Si consideramos procesamientos particulares, entityName puede tomar el valor por ejemplo "InfoPersona" indicando que se procesara informacion relativa a Persona, 
   * Se asigna el nombre "file" al archivo para identificacion, recordemos que esta api procesa un solo archivo
   */

  public function main() {
    /**
     * A diferencia de otras apis, la lectura de filtros se realiza dentro del controlador
     */
    try{
      $file = Filter::fileRequired("file"); 
      /**
       * Se asigna el nombre "file" al archivo para identificacion
       */
      $controller = Upload::getInstanceRequire($this->entityName);
      $data = $controller->main($file);
      echo json_encode($data);
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

}
