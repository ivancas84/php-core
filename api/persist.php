<?php

require_once("class/tools/Filter.php");
require_once("class/controller/Persist.php");
require_once("class/controller/Transaction.php");

try {
  $data = Filter::jsonPostRequired();
  /*$data = [
    ["action"=>"persist", "entity"=>"sede", "row"=>["numero"=>"20", "nombre"=>"Prueba"]],
    ["action"=>"persist", "entity"=>"asignatura", "row"=>["nombre"=>"Matemática"]]
  ];*/
  $persist = Persist::getInstanceRequire(ENTITY);
  $logs = $persist->main($data);

  Transaction::begin();
  Transaction::update(["descripcion"=> $persist->getSql(), "detalle" => implode(",",$persist->getDetail())]);
  Transaction::commit();

  echo json_encode($persist->getLogsKeys(["entity","ids","detail"]));

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}

