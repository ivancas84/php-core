<?php

require_once("class/tools/Filter.php");
require_once("class/controller/Dba.php");

try{

  $params = Filter::jsonPostRequired();
  //$params = ["domicilio"=>"1543133270054093"];
  $row = Dba::unique(ENTITY, $params);
  echo json_encode($row);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
