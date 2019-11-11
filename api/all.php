<?php

require_once("class/tools/Filter.php");
require_once("class/controller/Dba.php");

try{
  $display = Filter::jsonPostRequired();
  $render = RenderAux::getInstanceDisplay($display);
  $rows = Dba::all(ENTITY, $render);
  echo json_encode(EntitySqlo::getInstanceRequire("sede")->jsonAll($rows));

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
