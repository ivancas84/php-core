<?php
require_once("class/Filter.php");
require_once("function/stdclass_to_array.php");
require_once("class/model/Dba.php");
require_once("class/model/RenderAux.php");

require_once("function/stdclass_to_array.php");
try{

  $params = Filter::jsonPostRequired();
  $render = RenderAux::getInstanceParams($params);
  $count = Dba::count(ENTITY, $render);
  echo json_encode($count);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo $ex->getMessage();

}
