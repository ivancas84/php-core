<?php
require_once("class/Filter.php");
require_once("function/stdclass_to_array.php");
require_once("class/model/Dba.php");
require_once("function/stdclass_to_array.php");
try{

$postdata = file_get_contents("php://input");
$request = stdclass_to_array(json_decode($postdata));
print_r($request);

  //$params = Filter::postAll();
  //$params = json_decode($params);
  //print_r($params);
  //if(empty($params)) throw new Exception("Parametros no definidos");
  /**
   * El uso de parametros es dinamico
   * Se puede definir un parametro opcional "display" que posee un string en formato json para facilitar el uso de tipos basicos
   */

  // $render = Dba::renderParams($params);
  // $count = Dba::count(ENTITY, $render);
  // echo json_encode($count);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo $ex->getMessage();

}
