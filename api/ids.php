<?php

require_once("class/Filter.php");
require_once("class/model/Dba.php");
require_once("function/stdclass_to_array.php");

try{
  $display = Filter::jsonPostRequired();
  //$display = ["condition" => ["id","=",[18,35]]]; //prueba
  $render = RenderAux::getInstanceDisplay($display);
  $ids = Dba::ids(ENTITY, $render);
  echo json_encode($ids);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo $ex->getMessage();
}
