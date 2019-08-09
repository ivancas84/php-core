<?php

require_once("class/Filter.php");
require_once("class/Controller.php");
require_once("class/ExportData.php");
require_once("function/stdclass_to_array.php");


try{

  $f = Filter::requestRequired("data");
  $f_ = base64_decode($f);
  $f__ =  json_decode($f_);
  $filter = stdclass_to_array($f__);
  
  $render = Controller::render($filter);      
  $entityDao = Controller::entityDao($filter["entity"]);
  $rows = $entityDao->rows($render);
  $export = new ExportData($rows);

  switch($filter["export"]){
    case "xls": $export->exportXls(); break;    
    case "csv": $export->exportCsv(); break;
    default: $export->exportHtml(); break;
  }

} catch (Exception $ex) {
  error_log($ex->getTraceAsString());
  http_response_code(500);
  echo $ex->getMessage();
}
