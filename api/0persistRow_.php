<?php

require_once("class/Transaction_.php");
require_once("class/Controller.php");
require_once("class/Filter.php");
require_once("function/stdclass_to_array.php");

    
try{    
  $f = Filter::requestRequired("data");
  $f_ =  json_decode($f);
  $filter = stdclass_to_array($f_);
  
  $entity = $filter["entity"];
  $row = $filter["row"];

  $entityDao = Controller::entityDao($entity);
  
  $transaction = new Transaction_();
  $transaction->begin();
  $id = $entityDao->persist_($transaction, $row);     
  $transaction->commit();

  echo $id;  
} catch (Exception $ex) {
  http_response_code(500);
  echo $ex->getMessage();
  error_log($ex->getTraceAsString());
}