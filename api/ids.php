<?php

require_once("class/Filter.php");
require_once("class/model/Dba.php");
require_once("function/stdclass_to_array.php");

try{
  $params = Filter::postAll();
  if(empty($params)) throw new Exception("Parametros no definidos");

  //el uso de parametros es dinamico
  //se puede definir un parametro opcional "display" que posee un string en formato json para facilitar el uso de tipos basicos

  $render = Dba::renderParams($params);
  $ids = Dba::ids(ENTITY, $render);
  echo json_encode($ids);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo $ex->getMessage();
}
