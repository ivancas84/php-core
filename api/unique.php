<?php


require_once("class/Filter.php");
require_once("class/model/Dba.php");
require_once("function/stdclass_to_array.php");

try{

  $params_ = Filter::postRequired("display");
  if(empty($params_)) throw new Exception("Parametros no definidos");

  $params = stdclass_to_array(json_decode($params_));
  //el uso de parametros es dinamico
  //se puede definir un parametro opcional "data" que posee un string en formato json para facilitar el uso de tipos basicos

  $row = Dba::unique(ENTITY, $params);
  echo json_encode($row);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
