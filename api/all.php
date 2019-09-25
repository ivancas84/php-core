<?php

require_once("class/Filter.php");
require_once("class/model/Dba.php");

try{
  $params = Filter::jsonPostRequired();
  $render = RenderAux::getInstanceParams($params);
  $rows = Dba::all(ENTITY, $render);
  echo json_encode(EntitySqlo::getInstanceRequire("sede")->jsonAll($rows));

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
