<?php

require_once("class/tools/Filter.php");
require_once("class/controller/Persist.php");
require_once("class/controller/Transaction.php");

try {
  $data = Filter::jsonPostRequired();
  $logs = Persist::getInstanceRequire(ENTITY)->main($data);
  
  Transaction::begin();
  Transaction::update(["descripcion"=> $logs["sql"], "detalle" => implode(",",$logs["detail"])]);
  Transaction::commit();
  echo json_encode($response);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}

