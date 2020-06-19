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
      /*$data = [
        ["action"=>"persist", "entity"=>"sede", "row"=>["numero"=>"20", "nombre"=>"Prueba"]],
        ["action"=>"persist", "entity"=>"asignatura", "row"=>["nombre"=>"Matemática"]]
      ];*/
      $persistController = Persist::getInstanceRequire($this->entityName);
      $persistController->main($data);
      Transaction::begin();
      Transaction::update(["description"=> $persistController->getSql(), "detail" => implode(",",$persistController->getDetail())]);
      Transaction::commit();

      echo json_encode($persistController->getDetail());
      /**
       * se devuelve un array donde cada elemento es de la forma "entityId"
       */
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();

    }
  }
}




