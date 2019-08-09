<?php

require_once("class/Filter.php");
require_once("class/model/Dba.php");
require_once("function/stdclass_to_array.php");

try{
  $ids_ = Filter::post("ids");
  if(empty($ids_)) throw new Exception("Identificadores no definidos");

  $ids =  json_decode($ids_);
  $rows = Dba::getAll(ENTITY, $ids);
  echo json_encode($rows);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
