<?php

require_once("class/tools/Filter.php");
require_once("class/controller/Dba.php");

try {
  $ids = Filter::jsonPostRequired();
  if(empty($ids)) throw new Exception("Identificadores no definidos");
  $rows = Dba::getAll(ENTITY, $ids);
  echo json_encode(EntitySqlo::getInstanceRequire(ENTITY)->jsonAll($rows));

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
