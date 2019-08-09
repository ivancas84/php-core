<?php

require_once("class/Filter.php");
require_once("class/model/Dba.php");

try{

  $params = Filter::postAll();
  if(empty($params)) throw new Exception("Parametros no definidos");
  /**
   * El uso de parametros es dinamico
   * Se puede definir un parametro opcional "display" que posee un string en formato json para facilitar el uso de tipos basicos
   */

  $render = Dba::renderParams($params);
  $rows = Dba::all(ENTITY, $render);
  echo json_encode($rows);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
