<?php

require_once("class/model/Dba.php");

try{
  $details = Transaction::checkDetails();
  echo json_encode(["data" => $details]);

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
