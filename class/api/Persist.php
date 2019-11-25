<?php


require_once("class/tools/Filter.php");
require_once("class/controller/Transaction.php");
require_once("class/controller/Persist.php");

require_once("class/model/Sqlo.php");
require_once("function/stdclass_to_array.php");

abstract class PersistApi {
  protected $entityName;
  protected $persist;

  public function __construct(){
    $this->persist = new Persist();
  }

  public function main(){
    try {
      $data = Filter::jsonPostRequired();
      /*$data = [
        ["action"=>"persist", "entity"=>"sede", "row"=>["numero"=>"20", "nombre"=>"Prueba"]],
        ["action"=>"persist", "entity"=>"asignatura", "row"=>["nombre"=>"Matemática"]]
      ];*/
      $this->persist($data);
      $this->updateTransaction();  
      echo json_encode($this->persist->getLogsKeys(["entity","ids","detail"]));
    
    } catch (Exception $ex) {
      error_log($ex->getTraceAsString());
      http_response_code(500);
      echo $ex->getMessage();
    }
  }

  public function updateTransaction(){
    Transaction::begin();
    Transaction::update(["descripcion"=> $this->persist->getSql(), "detalle" => implode(",",$this->persist->getDetail())]);
    Transaction::commit();
  }

  public function persist($data){
    foreach($data as $d) {
      $entity = $d["entity"]; //entidad
      $row = (isset($d["row"])) ? $d["row"]: null; //row a procesar
      $rows = (isset($d["rows"])) ? $d["rows"]: null; //rows a procesar
      $id = (isset($d["id"])) ? $d["id"] : null; //id a eliminar
      $ids =  (isset($d["ids"])) ? $d["ids"] : null; //ids a eliminar
      $params = (isset($d["params"])) ? $d["params"] : null; //campos relacionados para identificacion
      /**
       * $params["name"]: Nombre de la clave foranea
       * $params["value]: Valor de la clave foranea
       */
  
      if(isset($row)) $this->persist->row($entity, $row);
      if(isset($rows)) $this->persist->row($entity, $row, $params);
      if(isset($id)) $this->persist->delete($entity, [$id], $params);
      if(isset($ids)) $this->persist->delete($entity, $ids, $params);
    }
  }

}




