<?php
require_once("class/tools/Filter.php");
require_once("function/stdclass_to_array.php");
require_once("class/controller/Dba.php");
require_once("class/model/RenderAux.php");

require_once("function/stdclass_to_array.php");
try{
  $display = Filter::jsonPostRequired();
  $render = RenderAux::getInstanceDisplay($display);
  $count = Dba::count(ENTITY, $render);
  echo json_encode($count);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo $ex->getMessage();

}
