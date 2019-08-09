<?php

require_once("class/Filter.php");
require_once("class/Controller.php");

require_once("class/db/Db.php");

try{
  $entity = Filter::requestRequired("entity");
  $transactionId = Filter::postRequired("transaction");
  $row = Filter::postArrayRequired("row");

  $transaction = new Transaction($transactionId);  
  $entityDao = Controller::entityDao($entity);
  $upload = $entityDao->upload_($transaction, $row); //retorna un conjunto de ids correspondientes a archivos

  $data = array_merge($row, $upload);
  return $entityDao->persist_($transaction, $data);

} catch (Exception $ex) {
  http_response_code(500);
  error_log($ex->getTraceAsString());
  echo ($ex->getMessage());
}

